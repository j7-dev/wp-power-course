import { Pagination } from './components/Pagination'
import $, { JQuery } from 'jquery'
import { site_url } from '../../utils/'
import {
	CommentForm,
	CommentItem,
	TCommentItemProps,
	Pagination,
	TPaginationProps,
} from './components'

export type TCommentAppProps = {
	queryParams: { [key: string]: any } | undefined
	navElement: string
	ratingProps?:
		| {
				name: string
		  }
		| undefined
}

export class CommentApp {
	$element: JQuery<HTMLElement>
	props?: TCommentAppProps
	_queryParams: { [key: string]: any } | undefined
	_pagination: TPaginationProps | undefined
	navElement: string
	ratingProps:
		| {
				name: string
		  }
		| undefined
	commentForm: CommentForm
	showForm: boolean
	showList: boolean
	_list: TCommentItemProps[]
	post_id: string // 商品 ID
	user_id: number // 使用者 ID
	comment_type: string // comment 類型
	_isLoading: boolean
	isSuccess: boolean
	isError: boolean
	total: number
	totalPages: number
	isInit: boolean

	constructor(element: string, props: TCommentAppProps) {
		this.$element = $(element)
		this.props = props
		this._queryParams = props?.queryParams
		this.navElement = props?.navElement
		this.ratingProps = props?.ratingProps
		this.showForm = this.$element.data('show_form') === 'yes'
		this.showList = this.$element.data('show_list') === 'yes'
		this.post_id = this.$element.data('post_id')
		this.user_id = Number(this.$element.data('user_id'))
		this.comment_type = this.$element.data('comment_type')
		this.isInit = true
		this._isLoading = false
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

	// 綁定換頁事件
	bindPaginationEvents(pagination: TPaginationProps) {
		const { current, totalPages, pageSize, total } = pagination
		this.$element
			.find('[data-pc="comment-pagination"]')
			.on('click', '.pc-pagination__pages', (e) => {
				const paged = Number($(e.currentTarget).data('page'))
				this.queryParams = {
					...this._queryParams,
					paged,
				}
			})

		this.$element
			.find('[data-pc="comment-pagination"]')
			.on('click', '.pc-pagination__prev', (e) => {
				if (current === 1) {
					return
				}
				const paged = current - 1
				this.queryParams = {
					...this._queryParams,
					paged,
				}
			})

		this.$element
			.find('[data-pc="comment-pagination"]')
			.on('click', '.pc-pagination__next', (e) => {
				if (current === totalPages) {
					return
				}
				const paged = current + 1
				this.queryParams = {
					...this._queryParams,
					paged,
				}
			})
	}

	render() {
		this.$element.html(/*html*/ `
			<div data-pc="comment-form"></div>
			<div data-pc="comment-list"></div>
			<div data-pc="comment-pagination"></div>
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
			// 如果不能留言要顯示原因
			const reason = this.$element.data('show_form')
			this.$element.find('[data-pc="comment-form"]').html(reason)
		}
	}

	// queryParams 改變時觸發
	set queryParams(value: { [key: string]: any }) {
		this._queryParams = value
		this.getComments({
			post_id: this.post_id,
		})
	}

	// pagination 改變時觸發
	set pagination(value: TPaginationProps) {
		this._pagination = value

		// render pagination
		const { totalPages = 1 } = value
		if (totalPages <= 1) {
			return
		}
		new Pagination(this.$element.find('[data-pc="comment-pagination"]'), value)

		this.bindPaginationEvents(value)
	}

	set isLoading(value: boolean) {
		this._isLoading = value
		if (value) {
			const loadingHtml = /*html*/ `
						<div class="h-[8.5rem] mt-2 rounded bg-gray-100 animate-pulse"></div>
						<div class="h-[8.5rem] mt-2 rounded bg-gray-100 animate-pulse"></div>
						<div class="h-[8.5rem] mt-2 rounded bg-gray-100 animate-pulse"></div>
						`
			this.$element.find('[data-pc="comment-list"]').html(loadingHtml)
		}
	}

	// list 改變時觸發
	set list(value: TCommentItemProps[]) {
		this._list = value

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
		this.isLoading = true
		$.ajax({
			url: `${site_url}/wp-json/power-course/comments`,
			type: 'get',
			data: {
				...this._queryParams,
				...data,
			},
			headers: {
				'X-WP-Nonce': (window as any).pc_data?.nonce,
			},
			timeout: 30000,
			success: (response: TCommentItemProps[], textStatus, jqXHR) => {
				const total = Number(jqXHR.getResponseHeader('X-WP-Total'))
				const totalPages = Number(jqXHR.getResponseHeader('X-WP-TotalPages'))
				const current = Number(jqXHR.getResponseHeader('X-WP-CurrentPage'))
				const pageSize = Number(jqXHR.getResponseHeader('X-WP-PageSize'))
				const pagination = {
					total,
					totalPages,
					current,
					pageSize,
				}

				this.isSuccess = 'success' === textStatus
				this.total = total
				this.totalPages = totalPages

				this.list = response ?? []
				this.pagination = pagination
			},
			error: (error) => {
				console.log('error', error)
				this.isSuccess = false
				this.isError = true
			},
			complete: (xhr) => {
				this.isLoading = false
				this.isInit = false
			},
		})
	}
}
