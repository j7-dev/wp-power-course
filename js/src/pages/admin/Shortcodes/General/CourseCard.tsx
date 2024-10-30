import React from 'react'
import sample1 from '@/assets/images/sample1.jpg'
import sample2 from '@/assets/images/sample2.jpg'
import sample3 from '@/assets/images/sample3.jpg'
import sample4 from '@/assets/images/sample4.jpg'
import sample5 from '@/assets/images/sample5.jpg'

type TCourseCard = {
	image: string
	title: string
	teachers: string[]
	regular_price: number
	sales_price?: number
	hours?: number
	minutes?: number
	total_students?: number
}

export const EXAMPLES: TCourseCard[] = [
	{
		image: sample1,
		title: 'OOO系統升級課｜讓自己快速升級，成為OOO',
		teachers: ['王曉明'],
		regular_price: 3000,
		sales_price: 2000,
		hours: 48,
		minutes: 26,
		total_students: 2682,
	},
	{
		image: sample2,
		title: 'OO快速公式課｜拆解OOO步驟，立即開始OOO',
		teachers: ['陳大文'],
		regular_price: 20000,
	},
	{
		image: sample3,
		title: 'OO全系列課程｜一次獲得OOO',
		teachers: ['黃小明'],
		regular_price: 9800,
		sales_price: 6200,
		hours: 51,
		minutes: 15,
		total_students: 4845,
	},
	{
		image: sample4,
		title: 'OOOO｜市面上唯一的OO課程',
		teachers: ['劉大文', '李曉明'],
		regular_price: 3050,
		hours: 20,
		minutes: 10,
	},
	{
		image: sample5,
		title: 'OOO系統升級課｜讓自己快速升級，成為OOO',
		teachers: ['王曉明'],
		regular_price: 3000,
		sales_price: 2000,
		total_students: 2682,
	},
]

const CourseCard = ({
	image,
	title,
	teachers,
	regular_price,
	sales_price,
	hours,
	minutes,
	total_students,
}: TCourseCard) => {
	return (
		<div className="pc-course-card">
			<div className="pc-course-card__image-wrap pc-course-card__image-wrap-product group mb-0">
				<img
					decoding="async"
					className="pc-course-card__image group-hover:scale-110 transition duration-300 ease-in-out"
					src={image}
					alt={title}
					loading="lazy"
				/>
			</div>

			<div className="flex gap-2 items-center my-2 h-6">
				<span className="bg-blue-100 [&_svg]:stroke-blue-500 text-blue-500 text-xs flex items-center px-2 py-1 rounded-md w-fit h-fit">
					<svg
						className="w-4 h-4 mr-1"
						viewBox="0 0 24 24"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<g strokeWidth="0"></g>
						<g strokeLinecap="round" strokeLinejoin="round"></g>
						<g>
							{' '}
							<path
								d="M12 10.4V20M12 10.4C12 8.15979 12 7.03969 11.564 6.18404C11.1805 5.43139 10.5686 4.81947 9.81596 4.43597C8.96031 4 7.84021 4 5.6 4H4.6C4.03995 4 3.75992 4 3.54601 4.10899C3.35785 4.20487 3.20487 4.35785 3.10899 4.54601C3 4.75992 3 5.03995 3 5.6V16.4C3 16.9601 3 17.2401 3.10899 17.454C3.20487 17.6422 3.35785 17.7951 3.54601 17.891C3.75992 18 4.03995 18 4.6 18H7.54668C8.08687 18 8.35696 18 8.61814 18.0466C8.84995 18.0879 9.0761 18.1563 9.29191 18.2506C9.53504 18.3567 9.75977 18.5065 10.2092 18.8062L12 20M12 10.4C12 8.15979 12 7.03969 12.436 6.18404C12.8195 5.43139 13.4314 4.81947 14.184 4.43597C15.0397 4 16.1598 4 18.4 4H19.4C19.9601 4 20.2401 4 20.454 4.10899C20.6422 4.20487 20.7951 4.35785 20.891 4.54601C21 4.75992 21 5.03995 21 5.6V16.4C21 16.9601 21 17.2401 20.891 17.454C20.7951 17.6422 20.6422 17.7951 20.454 17.891C20.2401 18 19.9601 18 19.4 18H16.4533C15.9131 18 15.643 18 15.3819 18.0466C15.15 18.0879 14.9239 18.1563 14.7081 18.2506C14.465 18.3567 14.2402 18.5065 13.7908 18.8062L12 20"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							></path>{' '}
						</g>
					</svg>
					立即上課
				</span>
			</div>
			<h3 className="pc-course-card__name">{title}</h3>
			<p className="pc-course-card__teachers !mb-4">
				by {teachers.map((teacher) => teacher).join(' & ')}
			</p>
			{regular_price && sales_price && (
				<div className="pc-course-card__price">
					<span className="sale-price">
						<del aria-hidden="true">
							<span className="woocommerce-Price-amount amount">
								<bdi>
									<span className="woocommerce-Price-currencySymbol">NT$</span>
									{regular_price.toLocaleString()}
								</bdi>
							</span>
						</del>{' '}
						<ins aria-hidden="true">
							<span className="woocommerce-Price-amount amount">
								<bdi>
									<span className="woocommerce-Price-currencySymbol">NT$</span>
									{sales_price.toLocaleString()}
								</bdi>
							</span>
						</ins>
					</span>
				</div>
			)}

			{regular_price && !sales_price && (
				<div className="pc-course-card__price">
					<span className="woocommerce-Price-amount amount">
						<bdi>
							<span className="woocommerce-Price-currencySymbol">NT$</span>
							{regular_price.toLocaleString()}
						</bdi>
					</span>
				</div>
			)}

			<div className="flex gap-2 items-center justify-between border-y border-x-0 border-solid border-gray-300 py-2 mt-2">
				<div className="text-gray-800 text-xs font-semibold flex items-center gap-1 [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg_path]:stroke-gray-400">
					<svg
						className="w-6 h-6"
						fill="none"
						viewBox="0 0 24 24"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path
							stroke="#1677ff"
							d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z"
							strokeWidth="2"
						></path>
						<path
							stroke="#1677ff"
							d="M12 7L12 11.5L12 11.5196C12 11.8197 12.15 12.1 12.3998 12.2665V12.2665L15 14"
							strokeWidth="2"
							strokeLinecap="round"
							strokeLinejoin="round"
						></path>
					</svg>
					{hours && minutes && `${hours} 小時 ${minutes} 分`}
					{!(hours && minutes) && '-'}
				</div>
				<div className="text-gray-800 text-xs font-semibold flex items-center gap-1 [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:fill-gray-400">
					<svg
						className="w-6 h-6"
						fill="#1677ff"
						viewBox="0 0 24 24"
						xmlns="http://www.w3.org/2000/svg"
					>
						<g>
							<path fill="none" d="M0 0h24v24H0z"></path>
							<path
								fillRule="nonzero"
								d="M12 11a5 5 0 0 1 5 5v6h-2v-6a3 3 0 0 0-2.824-2.995L12 13a3 3 0 0 0-2.995 2.824L9 16v6H7v-6a5 5 0 0 1 5-5zm-6.5 3c.279 0 .55.033.81.094a5.947 5.947 0 0 0-.301 1.575L6 16v.086a1.492 1.492 0 0 0-.356-.08L5.5 16a1.5 1.5 0 0 0-1.493 1.356L4 17.5V22H2v-4.5A3.5 3.5 0 0 1 5.5 14zm13 0a3.5 3.5 0 0 1 3.5 3.5V22h-2v-4.5a1.5 1.5 0 0 0-1.356-1.493L18.5 16c-.175 0-.343.03-.5.085V16c0-.666-.108-1.306-.309-1.904.259-.063.53-.096.809-.096zm-13-6a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zm13 0a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zm-13 2a.5.5 0 1 0 0 1 .5.5 0 0 0 0-1zm13 0a.5.5 0 1 0 0 1 .5.5 0 0 0 0-1zM12 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"
							></path>
						</g>
					</svg>
					{total_students ? total_students : '-'}
				</div>
			</div>
		</div>
	)
}

export default CourseCard
