import $, { JQuery } from 'jquery'
import { site_url } from '../../utils/'
import { CommentForm, CommentItem, TCommentItemProps } from './components'

export type TCommentAppProps = {
	queryParams: { [key: string]: any } | undefined
	navElement: string
	ratingProps?:
		| {
				name: string
		  }
		| undefined
}

export type TCommentQueryParams = {
	isInit: boolean
	post_id: undefined
	user_id: undefined
	isError: boolean
	isSuccess: boolean
	isLoading: boolean
	paged: number
	pageSize: number
	total: undefined
	totalPages: undefined
	list: TCommentItemProps[]
}

export class CommentApp {
	$element: JQuery<HTMLElement>
	props?: TCommentAppProps
	queryParams: { [key: string]: any } | undefined
	navElement: string
	ratingProps:
		| {
				name: string
		  }
		| undefined
	commentForm: CommentForm
	showForm: boolean
	showList: boolean
	list: TCommentItemProps[]
	post_id: string // 商品 ID
	isLoading: boolean
	isSuccess: boolean
	isError: boolean
	total: number
	totalPages: number
	isInit: boolean

	constructor(element: string, props: TCommentAppProps) {
		this.$element = $(element)
		this.props = props
		this.queryParams = props?.queryParams
		this.navElement = props?.navElement
		this.ratingProps = props?.ratingProps
		this.showForm = this.$element.data('show_form') === 'yes'
		this.showList = this.$element.data('show_list') === 'yes'
		this.post_id = this.$element.data('post_id')
		this.isInit = true
		this.isLoading = false
		this.render()
		this.createSubcomponents()
		this.bindEvents()
	}

	bindEvents() {
		// 初始化
		if (this.showList) {
			$(this.navElement).on('click', () => {
				if (this.isInit) {
					this.getComments({
						post_id: this.post_id,
					})
				}
			})
		}
	}

	render() {
		this.$element.html(/*html*/ `
			<div data-pc="comment-form"></div>
			<div data-pc="comment-list"></div>
		`)
	}

	createSubcomponents() {
		if (this.showForm) {
			// render comment form
			this.commentForm = new CommentForm(
				this.$element.find('[data-pc="comment-form"]'),
				{
					ratingProps: this.ratingProps,
					instance: this,
				},
			)
		} else {
			const reason = this.$element.data('show_form')
			this.$element.find('[data-pc="comment-form"]').html(reason)
		}
	}

	setLoading(value: boolean) {
		this.isLoading = value
		if (value) {
			const loadingHtml = /*html*/ `
		<div class="h-[8.5rem] mt-2 rounded bg-gray-100 animate-pulse"></div>
		<div class="h-[8.5rem] mt-2 rounded bg-gray-100 animate-pulse"></div>
		<div class="h-[8.5rem] mt-2 rounded bg-gray-100 animate-pulse"></div>
		`
			this.$element.find('[data-pc="comment-list"]').html(loadingHtml)
		}
	}
	setList(value: TCommentItemProps[]) {
		this.list = value

		if (!value.length) {
			this.$element.find('[data-pc="comment-list"]').html('目前沒有評價')
			return
		}

		// render comment items
		const nodes = value
			.map(
				({ id: comment_id }) =>
					`<div data-pc="comment-item-${comment_id}"></div>`,
			)
			.join('')
		this.$element.find('[data-pc="comment-list"]').html(nodes)
		value.forEach((commentItem) => {
			new CommentItem(
				this.$element.find(`[data-pc="comment-item-${commentItem.id}"]`),
				commentItem,
			)
		})
	}

	getComments(data: { [key: string]: any }) {
		this.setLoading(true)
		$.ajax({
			url: `${site_url}/wp-json/power-course/comments`,
			type: 'get',
			data: {
				...this.queryParams,
				...data,
			},
			headers: {
				'X-WP-Nonce': (window as any).pc_data?.nonce,
			},
			timeout: 30000,
			success: (response: TCommentItemProps[], textStatus, jqXHR) => {
				const total = jqXHR.getResponseHeader('X-WP-Total')
				const totalPages = jqXHR.getResponseHeader('X-WP-TotalPages')
				this.isSuccess = 'success' === textStatus
				this.total = total
				this.totalPages = totalPages

				this.setList(response ?? [])
			},
			error: (error) => {
				console.log('error', error)
				this.isSuccess = false
				this.isError = true
			},
			complete: (xhr) => {
				this.setLoading(false)
				this.isInit = false
			},
		})
	}
}
