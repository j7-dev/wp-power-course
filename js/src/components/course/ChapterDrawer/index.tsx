import { FC } from 'react'
import { Drawer, DrawerProps, Form, Input, Switch } from 'antd'
import { VideoInput, VideoLength } from '@/components/formItem'

const { Item } = Form

export const ChapterDrawer: FC<DrawerProps> = (drawerProps) => {
	const form = Form.useFormInstance()

	// 取得課程深度，用來判斷是否為子章節
	const watchDepth = Form.useWatch(['depth'], form)
	const label = watchDepth === 0 ? '章節' : '單元'

	return (
		<>
			<Drawer {...drawerProps}>
				{/* 這邊這個 form 只是為了調整 style */}
				<Form layout="vertical" form={form}>
					<Item name={['name']} label={`${label}名稱`}>
						<Input />
					</Item>
					{/* 如果深度為 0 清除 chapter_video*/}
					{watchDepth === 0 && (
						<Item name={['chapter_video']} hidden>
							<Input allowClear />
						</Item>
					)}
					{/*如果深度為 1 顯示上傳課程內容*/}
					{watchDepth === 1 && (
						<div className="mb-6 max-w-[20rem]">
							<p className="mb-3">上傳課程內容</p>
							<VideoInput name={['chapter_video']} />
						</div>
					)}
					{watchDepth === 1 && (
						<div className="mb-6 max-w-[20rem]">
							<p className="mb-3">課程時長</p>
							<VideoLength name={['chapter_length']} />
						</div>
					)}
					<Item
						name={['status']}
						label="發佈"
						initialValue="publish"
						getValueProps={(value) => ({ value: value === 'publish' })}
						normalize={(value) => (value ? 'publish' : 'draft')}
						hidden
					>
						<Switch checkedChildren="發佈" unCheckedChildren="草稿" />
					</Item>
					<Item name={['depth']} hidden>
						<Input />
					</Item>
					<Item name={['id']} hidden>
						<Input />
					</Item>
				</Form>
			</Drawer>
		</>
	)
}
