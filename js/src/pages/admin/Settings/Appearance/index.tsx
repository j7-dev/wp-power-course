import { memo, useEffect } from 'react'
import { Form, InputNumber } from 'antd'
import { FiSwitch } from '@/components/formItem'
import { Heading } from '@/components/general'
const { Item } = Form

const index = () => {
	const form = Form.useFormInstance()
	const watchFVT = Form.useWatch(['fix_video_and_tabs_mobile'], form)

	useEffect(() => {
		if ('no' === watchFVT) {
			form.setFieldValue('pc_header_offset', 0)
		}
	}, [watchFVT])

	return (
		<div className="flex flex-col md:flex-row gap-8">
			<div className="w-full max-w-[400px]">
				<Heading className="mt-8">課程銷售頁</Heading>
				<FiSwitch
					formItemProps={{
						name: ['fix_video_and_tabs_mobile'],
						label: '手機板時，影片以及 tabs 黏性(sticky)置頂',
						tooltip:
							'開啟後，手機板時下滑課程銷售頁，影片以及 tabs 會黏性(sticky)置頂，可能會蓋住 fixed 的 header',
						initialValue: 'yes',
					}}
				/>

				<Item
					hidden={'no' === watchFVT}
					name={['pc_header_offset']}
					label="偏移距離"
					tooltip="舉例來說，如果你的 Header 高度為 64px，請輸入 64，手機板時，影片以及 tabs 會黏性(sticky)置頂並偏移 64px"
					initialValue={0}
				>
					<InputNumber addonAfter="px" />
				</Item>
			</div>
			<div className="flex-1 h-auto md:h-screen md:overflow-y-auto"></div>
		</div>
	)
}

export default memo(index)
