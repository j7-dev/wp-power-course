import $, { JQuery } from 'jquery'
import { Rating } from './Rating'
import { CommentApp } from '../index'
import { site_url } from '../../../utils/'

export type TCommentItemProps = {
	id: string
	depth: number
	user: {
		id: string
		name: string
		avatar_url: string
	}
	rating: number
	comment_content: string
	comment_date: string
	comment_approved: '0' | '1' | 'spam'
	can_reply: boolean
	can_delete: boolean
	children: TCommentItemProps[]
}

export class CommentItem {
	$element: JQuery<HTMLElement>
	_props: TCommentItemProps
	rating: Rating
	appInstance: CommentApp
	_isLoading: boolean

	constructor(element, props) {
		this.$element = $(element)
		this._props = props
		this.appInstance = props.appInstance
		this.render()
		this._isLoading = false
	}

	set isLoading(value: boolean) {
		this._isLoading = value

		// this.$element
		// 	.find(`.pc-comment-item[data-comment_id="${this._props.id}"]`)
		// 	.toggleClass('animate-pulse', value)
	}

	// 綁定隱藏事件
	bindHideEvents(props) {
		this.$element
			.find('.pc-comment-item__hide-button')
			.first()
			.on('click', (e) => {
				e.stopPropagation()
				const commentId = $(e.currentTarget)
					.closest('.pc-comment-item')
					.data('comment_id')

				const CommentItemContentNode = this.$element.find(
					'.pc-comment-item__content',
				)

				const fromApproved = props.comment_approved
				const toApproved = fromApproved === '1' ? '0' : '1'

				$.ajax({
					url: `${site_url}/wp-json/power-course/comments/${commentId}/toggle-approved`,
					type: 'post',
					data: null,
					headers: {
						'X-WP-Nonce': (window as any).pc_data?.nonce,
					},
					timeout: 30000,
					success: (response, textStatus, jqXHR) => {
						const { code, message } = response

						if (200 === code) {
							this.appInstance.getComments({}, false)
						} else {
							CommentItemContentNode.find('.pc-comment-item__reply-form')
								.text(message)
								.removeClass('text-green-500')
								.addClass('text-red-500')
						}
					},
					error: (error) => {
						console.log('⭐  error:', error)
						const message = error?.responseJSON?.message || '發生錯誤'
						CommentItemContentNode.find('.pc-comment-item__reply-form')
							.text(message)
							.removeClass('text-green-500')
							.addClass('text-red-500')
					},
					complete: (xhr) => {},
				})
			})
	}

	render() {
		const { user, comment_content, comment_date, children, depth, id } =
			this._props
		const childrenHTML = children
			.map(
				({ id: childId }) =>
					`<div data-comment_id="${childId}" class="pc-comment-item"></div>`,
			)
			.join('')
		const bgColor = depth % 2 === 0 ? 'bg-gray-100' : 'bg-gray-50'

		const comment_approved = this._props.comment_approved === '1'
		const user_role = this.appInstance.user_role
		const comment_type = this.appInstance.comment_type
		const can_reply = !(comment_type === 'review' && user_role === 'user')

		this.$element.html(/*html*/ `
			<div class="p-6 mt-2 rounded ${bgColor} ${comment_approved ? '' : 'border-2 border-dashed border-gray-400'}">
				<div class="flex gap-4">
					<div class="w-10 h-10 rounded-full overflow-hidden relative">
						<img src="${user.avatar_url}" loading="lazy" class="w-full h-full object-cover relative z-20">
						<div class="absolute top-0 left-0 w-full h-full bg-gray-400 animate-pulse z-10"></div>
					</div>
					<div class="flex-1">
						<div class="flex justify-between text-sm">
							<div class="">${user.name}</div>
							<div class="pc-comment-item__rating"></div>
						</div>
						<p class="text-gray-400 text-xs mb-4">${comment_date} ${user_role === 'admin' ? `#${id}` : ''} ${comment_approved ? '' : '留言已隱藏'}</p>
						<div class="pc-comment-item__content text-sm [&_p]:mb-0">
							${comment_content}
							<div class="mt-2 flex gap-x-2 text-xs text-primary [&_span]:cursor-pointer">
								${can_reply ? '<span class="pc-comment-item__reply-button">回覆</span>' : ''}
								${user_role === 'admin' ? `<span class="pc-comment-item__hide-button">${comment_approved ? '隱藏' : '顯示'}</span>` : ''}
							</div>
							<div class="pc-comment-item__reply-form"></div>
						</div>
						${childrenHTML}
					</div>
				</div>
			</div>
		`)
		this.createSubcomponents()
		this.bindHideEvents(this._props)
	}

	createSubcomponents() {
		const { rating, children } = this._props
		if (rating) {
			this.rating = new Rating(this.$element.find('.pc-comment-item__rating'), {
				value: rating,
				disabled: true,
				name: 'rating-10',
			})
		}

		children.forEach((child) => {
			this.appInstance.commentItems.push(
				new CommentItem(
					this.$element.find(`.pc-comment-item[data-comment_id="${child.id}"]`),
					{
						...child,
						appInstance: this.appInstance,
					},
				),
			)
		})
	}
}
