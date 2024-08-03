import $, { JQuery } from 'jquery'
import { Rating } from './Rating'

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
	can_reply: boolean
	can_delete: boolean
	children: TCommentItemProps[]
}

export class CommentItem {
	$element: JQuery<HTMLElement>
	props: TCommentItemProps
	rating: Rating

	constructor(element, props) {
		this.$element = $(element)
		this.props = props
		this.render()
		this.createSubcomponents()
	}

	render() {
		const { user, comment_content, comment_date, children, depth } = this.props
		const childrenHTML = children
			.map(
				({ id: childId }) =>
					`<div data-comment_id="${childId}" class="pc-comment-item"></div>`,
			)
			.join('')
		const bgColor = depth % 2 === 0 ? 'bg-gray-100' : 'bg-gray-50'

		this.$element.html(/*html*/ `
			<div class="p-6 mt-2 rounded ${bgColor}">
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
						<p class="text-gray-400 text-xs mb-4">${comment_date}</p>
						<div class="pc-comment-item__content text-sm [&_p]:mb-0">
							${comment_content}
							<div class="mt-2 flex gap-x-2 text-xs text-primary [&_span]:cursor-pointer">
								<span class="pc-comment-item__reply-button">回覆</span>
								<span class="pc-comment-item__hide-button">隱藏</span>
							</div>
							<div class="pc-comment-item__reply-form"></div>
						</div>
						${childrenHTML}
					</div>
				</div>
			</div>
		`)
	}

	createSubcomponents() {
		const { rating, children } = this.props
		if (rating) {
			this.rating = new Rating(this.$element.find('.pc-comment-item__rating'), {
				value: rating,
				disabled: true,
				name: 'rating-10',
			})
		}

		children.forEach((child) => {
			new CommentItem(
				this.$element.find(`.pc-comment-item[data-comment_id="${child.id}"]`),
				child,
			)
		})
	}
}
