<?php
/**
 * Sidebar for classroom
 */

use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Chapter\Core\CPT;


$default_args = [
	'product' => $GLOBALS['course'] ?? null,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args($args, $default_args);

[
	'product' => $product,
] = $args;

if (! ( $product instanceof \WC_Product )) {
	return;
}

$count_all_chapters       = count(ChapterUtils::get_flatten_post_ids($product->get_id()));
$course_length_in_minutes = CourseUtils::get_course_length($product, 'minute');
$chapters_html            = ChapterUtils::get_children_posts_html_uncached($product->get_id());

/** @var \WP_Post $chapter */
global $chapter;
$ancestor_ids        = get_ancestors($chapter->ID, CPT::POST_TYPE, 'post_type');
$ancestor_ids_string = '[' . implode(',', $ancestor_ids) . ']';

?>
<style>
	.icon-arrow svg {
		transform: rotate(0deg);
		transition: all 0.3s ease-in-out;
	}

	.expanded .icon-arrow svg {
		transform: rotate(90deg);
		transition: all 0.3s ease-in-out;
	}
</style>

<a href="<?php echo $product->get_permalink(); ?>" class="block text-lg font-bold tracking-wide my-0 line-clamp-2 h-14 pt-5 pl-0 lg:pl-4 hover:text-primary transition-colors"><?php echo $product->get_title(); ?></a>

<div class="flex justify-between items-center py-4 px-0 lg:px-4">
	<span class="text-base tracking-wide font-bold">課程章節</span>
	<span class="text-sm text-gray-400"><?php echo $count_all_chapters; ?> 個章節<?php echo $course_length_in_minutes ? "，{$course_length_in_minutes} 分鐘" : ''; ?></span>
</div>
<div id="pc-sider__main-chapters" class="pc-sider-chapters overflow-y-auto lg:ml-0 lg:mr-0" data-ancestor_ids="<?php echo $ancestor_ids_string; ?>">
	<?php echo $chapters_html; ?>
</div>


<script type="module" async>
	(function($) {
		$(document).ready(function() {
			const $el = $('#pc-sider__main-chapters')
			if(!$el.length){
				console.error('#pc-sider__main-chapters 節點不存在')
				return
			}

			// 點擊箭頭展開或收合章節
			$el.on('click', 'li', function(e) {
				e.preventDefault();
				e.stopPropagation();

				const $li = $(this);
				const href = $li.data('href');
				const isLocked = $li.data('locked');
				const $sub_ul = $li.next('ul'); // 子章節

				if ($sub_ul.length > 0) {
					$li.toggleClass('expanded'); // 如果有找到子章節
					$sub_ul.slideToggle('fast'); // 如果有找到子章節
				}

				// 如果點擊的是箭頭，就只展開/收合，不要跳轉頁面
				if ($(e.target).closest('.icon-arrow').length > 0) {
					return;
				}

				// 被鎖定的章節顯示 Toast 警告，不跳轉
				if (isLocked) {
					showLockedToast();
					return;
				}

				if (href) {
					window.location.href = href;
				}
			})

			// 跳轉頁面前先記錄展開的章節
			$el.on('click', 'li a', function(e) {
				// 阻止原本的超連結行為
				e.preventDefault();
				e.stopPropagation();

				handle_save_expanded_post_ids()

				// 然後才跳轉頁面
				const href = $(this).attr('href');
				window.location.href = href;
			})

			// 離開頁面時，記錄展開的章節
			$(window).on('beforeunload', function(e) {
				// 避免顯示確認框，不要使用 preventDefault()
				handle_save_expanded_post_ids()
			});

			restore_expanded_post_ids();

			// 顯示鎖定章節的 Toast 警告
			function showLockedToast() {
				// 避免重複顯示
				if ($('.pc-locked-toast').length > 0) {
					return;
				}

				const $toast = $('<div class="pc-locked-toast pc-alert pc-alert-warning fixed bottom-4 left-1/2 -translate-x-1/2 z-[9999] shadow-lg w-auto max-w-sm text-sm" role="alert"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg><span>請先完成前面的章節才能觀看此章節</span></div>');
				$('body').append($toast);
				setTimeout(function() {
					$toast.fadeOut(300, function() {
						$toast.remove();
					});
				}, 3000);
			}

			// 把當前展開的章節 id 先記錄起來
			function handle_save_expanded_post_ids() {
				const expanded_post_ids = $el.find('li.expanded').map(function() {
					return $(this).data('post-id');
				}).get();

				// 記錄到 sessionStorage
				sessionStorage.setItem('expanded_post_ids', JSON.stringify(expanded_post_ids));
			}

			// 恢復章節的展開狀態
			function restore_expanded_post_ids() {
				const expanded_post_ids_string = sessionStorage.getItem('expanded_post_ids') // 拿不到為 null
				const expanded_post_ids = expanded_post_ids_string ? JSON.parse(expanded_post_ids_string) : [];

				// 當前文章的祖先也要展開，所以也必須加入
				const ancestor_ids = $el.data('ancestor_ids');

				const all_expanded_post_ids = [...new Set([
					...expanded_post_ids,
					...ancestor_ids,
				])];

				if (all_expanded_post_ids.length > 0) {
					all_expanded_post_ids.forEach(function(post_id) {
						const $li = $el.find(`li[data-post-id="${post_id}"]`);
						if ($li.length > 0) {
							$li.addClass('expanded');
							$li.next('ul').show();
						}
					});
				}

				// 恢復完畢，清除 sessionStorage，顯示 #pc-sider__main-chapters
				sessionStorage.removeItem('expanded_post_ids');
				$el.show();
			}
		})
	})(jQuery)
</script>
