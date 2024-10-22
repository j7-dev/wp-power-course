import { FC } from 'react'
import {
	TChapterRecord,
	TCourseRecord,
} from '@/pages/admin/Courses/CourseTable/types'
import { TProductRecord } from '@/components/product/ProductTable/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { Image } from 'antd'
import { EyeOutlined } from '@ant-design/icons'

export const ProductName: FC<{
	record: TProductRecord | TCourseRecord | TChapterRecord
	onClick?: () => void
}> = ({ record, onClick }) => {
	const { id, sku = '', name, images } = record
	const image_url = images?.[0]?.url || defaultImage

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
						onClick={onClick ? onClick : undefined}
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
