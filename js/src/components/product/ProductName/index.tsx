import { FC } from 'react'
import {
	TChapterRecord,
	TCourseRecord,
} from '@/pages/admin/Courses/CourseSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { Image, message } from 'antd'
import { EyeOutlined } from '@ant-design/icons'

export const ProductName: FC<{
	record: TCourseRecord | TChapterRecord
	show: {
		showCourseDrawer: (_record: TCourseRecord) => () => void
		showChapterDrawer: (_record: TChapterRecord) => () => void
	}
	loading?: boolean
}> = ({ record, show, loading = false }) => {
	const { id, sku = '', name, images, type } = record
	const { showChapterDrawer, showCourseDrawer } = show
	const image_url = images?.[0]?.url || defaultImage
	const isChapter = type === 'chapter'

	const handleClick = () => {
		if (!loading) {
			if (isChapter) {
				showChapterDrawer(record as TChapterRecord)()
			} else {
				showCourseDrawer(record as TCourseRecord)()
			}
		} else {
			message.error('請等待儲存後再進行編輯')
		}
	}

	return (
		<>
			<div className="flex">
				<div className="mr-4">
					<Image
						className="rounded-md object-cover"
						preview={{
							mask: <EyeOutlined />,
							maskClassName: 'rounded-md',
							forceRender: true,
						}}
						width={72}
						height={40}
						src={image_url || defaultImage}
						fallback={defaultImage}
					/>
				</div>
				<div className="flex-1">
					<p
						className="mb-1 text-primary hover:text-primary/70 cursor-pointer"
						onClick={handleClick}
					>
						{renderHTML(name)}
					</p>
					<div className="flex text-[0.675rem] text-gray-500">
						<span className="pr-3">{`ID: ${id}`}</span>
						{sku && <span className="pr-3">{`SKU: ${sku}`}</span>}
					</div>
				</div>
			</div>
		</>
	)
}
