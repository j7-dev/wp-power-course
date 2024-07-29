import { lazy } from 'react'
/* eslint-disable @typescript-eslint/ban-ts-comment */
import $ from 'jquery'
import { store, commentQueryParamsAtom } from '../store'
import { site_url } from '../utils'

export type TCommentItem = {
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
	children: TCommentItem[]
}

// Comment Form 組件
const CommentForm = (props) => {
	const { ratingProps } = props
	const name = ratingProps?.name
	return /*html*/ `
	<div class="pc-comment-form bg-gray-100 p-6 mb-2 rounded">
		<p class="text-gray-800 text-base font-bold mb-0">新增評價</p>
		<div class="mb-2">
			${Rating({ value: 5, disabled: false, name })}
		</div>
		<textarea class="mb-2 rounded h-24 bg-white" id="comment" name="comment" rows="4"></textarea>
		<div class="flex justify-end gap-4 items-center">
			<p class="pc-comment-form__message text-sm m-0"></p>
			<button type="button" class="pc-comment-form__submit pc-btn px-4 pc-btn-primary text-white pc-btn-sm"><span class="pc-loading pc-loading-spinner h-4 w-4 tw-hidden"></span>送出</button>
		</div>
	</div>
	`
}

const addCommentFormEvent = (props) => {
	const { ratingProps } = props
	const name = ratingProps?.name
	$('.pc-comment-form__submit').on('click', function () {
		const rating = $(this)
			.closest('.pc-comment-form')
			.find(`input[name="${name}"]:checked`)
			.val()
		const comment_content = $(this)
			.closest('.pc-comment-form')
			.find('textarea')
			.val()

		const comment_post_ID = $('.pc-comment').data('post_id')

		const commentFormInstance = $(this).closest('.pc-comment-form')

		createComments({
			comment_post_ID,
			rating,
			comment_content,
			instance: commentFormInstance,
		})
	})
}

// Comment 組件
const CommentItem = (item: TCommentItem) => {
	const { user, comment_content, comment_date, rating, children, depth } = item
	const childrenHTML = children.map(CommentItem).join('')
	const bgColor = depth % 2 === 0 ? 'bg-gray-100' : 'bg-gray-50'

	return /*html*/ `
	<div class="p-6 mt-2 rounded ${bgColor}">
		<div class="flex gap-4">
			<div class="w-10 h-10 rounded-full overflow-hidden relative">
				<img src="${user.avatar_url}" loading="lazy" class="w-full h-full object-cover relative z-20">
				<div class="absolute top-0 left-0 w-full h-full bg-gray-400 animate-pulse z-10"></div>
			</div>
			<div class="flex-1">
				<div class="flex justify-between text-sm">
					<div class="">${user.name}</div>
					<div>${rating ? Rating({ value: rating, disabled: true, name: 'rating-10' }) : ''}</div>
				</div>
				<p class="text-gray-400 text-xs mb-4">${comment_date}</p>
				<div class="mb-4 text-sm [&_p]:mb-0">${comment_content}</div>
				${childrenHTML}
			</div>
		</div>
	</div>`
}

// Rating 組件
const Rating = (props: { value: number; disabled: boolean; name: string }) => {
	const { value = 0, disabled = false, name = 'rating-10' } = props

	const disabledAttr = disabled ? 'disabled' : ''
	const inputHTML = Array.from({ length: 5 }, (_, index) => {
		const checked = index + 1 === value
		return `<input type="radio" value="${index + 1}" name="${name}" class="bg-yellow-400 pc-mask pc-mask-star" ${disabledAttr} ${checked ? 'checked="checked"' : ''} />`
	}).join('\n')

	return /*html*/ `
	<div class="pc-rating pc-rating-sm">${inputHTML}</div>`
}

// Loading 組件
const LoadingComment = () => {
	return /*html*/ `
	<div class="h-[8.5rem] mt-2 rounded bg-gray-100 animate-pulse"></div>
	`
}

export function comment() {
	const CommentContainer = $('.pc-comment')
	const CommentNav = $('#tab-nav-review')
	if (CommentContainer.length === 0 || CommentNav.length === 0) {
		return
	}

	const reviewProps = {
		ratingProps: {
			name: 'course-review',
		},
	}
	CommentContainer.before(CommentForm(reviewProps))
	addCommentFormEvent(reviewProps)

	store.sub(commentQueryParamsAtom, () => {
		const { isLoading, isSuccess, isError, paged, list } = store.get(
			commentQueryParamsAtom,
		)

		if (!!list?.length && isSuccess) {
			CommentContainer.html(list.map(CommentItem).join(''))
		}

		if (isLoading) {
			const loadingHTML = Array.from({ length: 3 }, () =>
				LoadingComment(),
			).join('')
			CommentContainer.html(loadingHTML)
		}

		if (isSuccess) {
		}

		if (isError) {
		}
	})

	// 切換事件觸發 init query
	CommentNav.on('click', function (e) {
		const post_id = CommentContainer.data('post_id')

		const { isInit } = store.get(commentQueryParamsAtom)

		if (isInit) {
			getComments({
				post_id,
			})
		}
	})
}

function getComments(args?: { [key: string]: any }) {
	store.set(commentQueryParamsAtom, (prev) => ({
		...prev,
		isLoading: true,
	}))

	$.ajax({
		url: `${site_url}/wp-json/power-course/comments`,
		type: 'get',
		data: args,
		headers: {
			'X-WP-Nonce': (window as any).pc_data?.nonce,
		},
		timeout: 30000,
		success(response: TCommentItem[], textStatus, jqXHR) {
			const total = jqXHR.getResponseHeader('X-WP-Total')
			const totalPages = jqXHR.getResponseHeader('X-WP-TotalPages')
			store.set(commentQueryParamsAtom, (prev) => ({
				...prev,
				isSuccess: 'success' === textStatus,
				total,
				totalPages,
				list: response ?? [],
			}))
		},
		error(error) {
			console.log('error', error)
			store.set(commentQueryParamsAtom, (prev) => ({
				...prev,
				isSuccess: false,
				isError: true,
			}))
		},
		complete(xhr) {
			store.set(commentQueryParamsAtom, (prev) => ({
				...prev,
				isLoading: false,
				isInit: false,
			}))
		},
	})
}

function createComments(args?: { [key: string]: any }) {
	// @ts-ignore
	const { instance, ...restData } = args

	setCommentFormLoading(instance, true)

	$.ajax({
		url: `${site_url}/wp-json/power-course/comments`,
		type: 'post',
		data: restData,
		headers: {
			'X-WP-Nonce': (window as any).pc_data?.nonce,
		},
		timeout: 30000,
		success(response, textStatus, jqXHR) {
			const { code, message } = response

			if (200 === code) {
				const post_id = $('.pc-comment').data('post_id')
				instance
					.find('.pc-comment-form__message')
					.text(message)
					.addClass('text-green-500')

				getComments({
					post_id,
				})
			} else {
				instance
					.find('.pc-comment-form__message')
					.text(message)
					.addClass('text-red-500')
			}
		},
		error(error) {
			console.log('⭐  error:', error)
			instance
				.find('.pc-comment-form__message')
				.text('發生錯誤')
				.addClass('text-red-500')
		},
		complete(xhr) {
			setCommentFormLoading(instance, false)
		},
	})
}

function setCommentFormLoading(instance, isLoading: boolean) {
	if (isLoading) {
		instance.find('.pc-comment-form__submit').addClass('pc-btn-disabled')
		instance.find('textarea').attr('disabled', 'disabled')
		instance
			.find('.pc-comment-form__submit .pc-loading')
			.removeClass('tw-hidden')
	} else {
		instance.find('.pc-comment-form__submit').removeClass('pc-btn-disabled')
		instance.find('textarea').removeAttr('disabled')
		instance.find('.pc-comment-form__submit .pc-loading').addClass('tw-hidden')
	}
}
