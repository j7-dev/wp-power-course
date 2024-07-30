import $, { JQuery } from 'jquery'
import { Rating, TRatingProps } from './Rating'
import { site_url } from '../../../utils/'
import { CommentApp } from '../index'

export type TCommentFormProps = {
	ratingProps: Partial<TRatingProps>
	instance: CommentApp
}

export class CommentForm {
	$element: JQuery<HTMLElement>
	props: {
		ratingProps: Partial<TRatingProps>
		instance: CommentApp
	}
	post_id: string // 商品 ID
	rating: Rating

	constructor(element, props) {
		this.$element = $(element)
		this.props = props
		this.post_id = $('.pc-comment').data('post_id')
		this.render()
		this.createSubcomponents()
		this.bindEvents()
	}

	bindEvents() {
		this.$element.on('click', '.pc-comment-form__submit', () => this.add())
	}

	add() {
		const rating = this.$element
			.find(`input[name="${this.props.ratingProps.name}"]:checked`)
			.val()
		const comment_content = this.$element.find('textarea').val()
		const comment_post_ID = this.post_id

		this.setLoading(true)

		$.ajax({
			url: `${site_url}/wp-json/power-course/comments`,
			type: 'post',
			data: {
				comment_post_ID,
				rating,
				comment_content,
			},
			headers: {
				'X-WP-Nonce': (window as any).pc_data?.nonce,
			},
			timeout: 30000,
			success: (response, textStatus, jqXHR) => {
				const { code, message } = response

				if (200 === code) {
					const post_id = $('.pc-comment').data('post_id')
					this.$element
						.find('.pc-comment-form__message')
						.text(message)
						.addClass('text-green-500')

					this.$element.find('textarea').val('')

					this.props.instance.getComments({
						post_id,
					})
				} else {
					this.$element
						.find('.pc-comment-form__message')
						.text(message)
						.addClass('text-red-500')
				}
			},
			error: (error) => {
				console.log('⭐  error:', error)
				const message = error?.responseJSON?.message || '發生錯誤'
				this.$element
					.find('.pc-comment-form__message')
					.text(message)
					.addClass('text-red-500')
			},
			complete: (xhr) => {
				this.setLoading(false)
			},
		})
	}

	setLoading(isLoading: boolean) {
		if (isLoading) {
			this.$element.find('.pc-comment-form__submit').addClass('pc-btn-disabled')
			this.$element.find('textarea').attr('disabled', 'disabled')
			this.$element
				.find('.pc-comment-form__submit .pc-loading')
				.removeClass('tw-hidden')
		} else {
			this.$element
				.find('.pc-comment-form__submit')
				.removeClass('pc-btn-disabled')
			this.$element.find('textarea').removeAttr('disabled')
			this.$element
				.find('.pc-comment-form__submit .pc-loading')
				.addClass('tw-hidden')
		}
	}

	render() {
		this.$element.html(/*html*/ `
			<div class="pc-comment-form bg-gray-100 p-6 mb-2 rounded">
				<p class="text-gray-800 text-base font-bold mb-0">新增評價</p>
				<div data-pc="rating" class="mb-2"></div>
				<textarea class="mb-2 rounded h-24 bg-white" id="comment" name="comment" rows="4"></textarea>
				<div class="flex justify-end gap-4 items-center">
					<p class="pc-comment-form__message text-sm m-0"></p>
					<button type="button" class="pc-comment-form__submit pc-btn px-4 pc-btn-primary text-white pc-btn-sm"><span class="pc-loading pc-loading-spinner h-4 w-4 tw-hidden"></span>送出</button>
				</div>
			</div>
		`)
	}

	createSubcomponents() {
		this.rating = new Rating(this.$element.find('[data-pc="rating"]'), {
			value: 5,
			disabled: false,
			name: 'rating',
			...this.props.ratingProps,
		})
	}
}
