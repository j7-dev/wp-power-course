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

// 線性觀看模式：取得鎖定章節資料
$product_id      = $product->get_id();
$is_linear       = CourseUtils::is_linear_chapter_mode( $product_id );
$current_user_id = \get_current_user_id();
$is_admin        = \current_user_can( 'manage_woocommerce' );
$locked_ids_json = $is_linear ? \esc_attr( (string) wp_json_encode( ChapterUtils::get_locked_chapter_ids( $current_user_id, $product_id ) ) ) : '[]';

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
<div id="pc-sider__main-chapters" class="pc-sider-chapters overflow-y-auto lg:ml-0 lg:mr-0"
	data-ancestor_ids="<?php echo $ancestor_ids_string; ?>"
	data-linear="<?php echo $is_linear ? 'yes' : 'no'; ?>"
	data-locked-ids="<?php echo $locked_ids_json; ?>"
	data-is-admin="<?php echo $is_admin ? 'yes' : 'no'; ?>"
>
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
				const $sub_ul = $li.next('ul'); // 子章節

				// 線性觀看模式：阻擋點擊鎖定章節（非管理員）
				const isAdminUser = $el.data('is-admin') === 'yes'
				if ($li.attr('data-locked') === 'true' && !isAdminUser) {
					alert('請先完成前面的章節後再觀看此章節')
					return
				}

				if ($sub_ul.length > 0) {
					$li.toggleClass('expanded'); // 如果有找到子章節
					$sub_ul.slideToggle('fast'); // 如果有找到子章節
				}

				// 如果點擊的是箭頭，就只展開/收合，不要跳轉頁面
				if ($(e.target).closest('.icon-arrow').length > 0) {
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

			// 線性觀看模式：初始化鎖定 UI
			initLinearLockUI();

			// 線性觀看模式：初始化鎖定 UI
			function initLinearLockUI() {
				const isLinear = $el.data('linear') === 'yes'
				const isAdminUser = $el.data('is-admin') === 'yes'
				const lockedIds = $el.data('locked-ids') || []

				if (!isLinear || lockedIds.length === 0) {
					return
				}

				lockedIds.forEach(function(postId) {
					const $li = $el.find(`li[data-post-id="${postId}"]`)
					if ($li.length === 0) {
						return
					}

					$li.attr('data-locked', 'true')

					// 替換圖示為鎖頭
					const $icon = $li.find('.pc-chapter-icon')
					if ($icon.length > 0) {
						$icon.html('<div class="pc-tooltip pc-tooltip-right h-6" data-tip="請先完成前面的章節"><svg class="size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 1C9.23858 1 7 3.23858 7 6V8H6C4.89543 8 4 8.89543 4 10V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V10C20 8.89543 19.1046 8 18 8H17V6C17 3.23858 14.7614 1 12 1ZM15 8V6C15 4.34315 13.6569 3 12 3C10.3431 3 9 4.34315 9 6V8H15ZM12 13C11.4477 13 11 13.4477 11 14V17C11 17.5523 11.4477 18 12 18C12.5523 18 13 17.5523 13 17V14C13 13.4477 12.5523 13 12 13Z" fill="#9ca3af"/></svg></div>')
					}

					// 非管理員時禁用點擊（移除 href 避免跳轉）
					if (!isAdminUser) {
						$li.addClass('opacity-50 cursor-not-allowed')
						$li.removeAttr('data-href')
					}
				})
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
