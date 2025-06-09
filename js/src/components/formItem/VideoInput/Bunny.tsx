import { Form, FormItemProps, Button } from 'antd'
import { FC } from 'react'
import { DeleteOutlined } from '@ant-design/icons'
import { TVideo } from './types'
import { useEnv } from '@/hooks'
import { MediaLibraryModal, useMediaLibraryModal } from 'antd-toolkit/refine'

const { Item } = Form
const Bunny: FC<FormItemProps> = (formItemProps) => {
	const { BUNNY_LIBRARY_ID } = useEnv()
	const form = Form.useFormInstance()
	const name = formItemProps?.name
	if (!name) {
		throw new Error('name is required')
	}

	const recordId = Form.useWatch(['id'], form)

	// 取得後端傳來的 saved video
	const savedVideo: TVideo | undefined = Form.useWatch(name, form)

	const { show, close, modalProps, setModalProps, ...mediaLibraryProps } =
		useMediaLibraryModal({
			onConfirm: (selectedItems) => {
				form.setFieldValue(name, {
					type: 'bunny-stream-api',
					id: selectedItems?.[0]?.guid || '',
					meta: {},
				})
			},
		})

	const isEmpty = savedVideo?.id === ''

	const videoUrl = `https://iframe.mediadelivery.net/embed/${BUNNY_LIBRARY_ID}/${savedVideo?.id}?autoplay=false&loop=false&muted=false&preload=true&responsive=true`

	const handleDelete = () => {
		form.setFieldValue(name, {
			type: 'none',
			id: '',
			meta: {},
		})
	}

	return (
		<div className="relative">
			<Button
				size="small"
				type="link"
				className="ml-0 mb-2 pl-0"
				onClick={show}
			>
				開啟 Bunny 媒體庫
			</Button>
			<MediaLibraryModal
				modalProps={modalProps}
				mediaLibraryProps={{
					...mediaLibraryProps,
					limit: 1,
				}}
			/>
			<Item hidden {...formItemProps} />
			{/* 如果章節已經有存影片，則顯示影片，有瀏覽器 preview，則以 瀏覽器 preview 優先 */}
			{recordId && !isEmpty && (
				<div className="relative aspect-video rounded-lg border border-dashed border-gray-300">
					<div className="absolute w-full h-full top-0 left-0 p-2">
						<div className="w-full h-full rounded-xl overflow-hidden">
							<div
								className={`rounded-xl bg-gray-200 ${!isEmpty ? 'tw-block' : 'tw-hidden'}`}
								style={{
									position: 'relative',
									paddingTop: '56.25%',
								}}
							>
								<iframe
									className="border-0 absolute top-0 left-0 w-full h-full rounded-xl"
									src={videoUrl}
									loading="lazy"
									allow="encrypted-media;picture-in-picture;"
									allowFullScreen={true}
								></iframe>

								<div
									onClick={handleDelete}
									className="group absolute top-4 right-4 rounded-md size-12 bg-white shadow-lg flex justify-center items-center transition duration-300 hover:bg-red-500 cursor-pointer"
								>
									<DeleteOutlined className="text-red-500 group-hover:text-white" />
								</div>
							</div>
						</div>
					</div>
				</div>
			)}
		</div>
	)
}

export default Bunny
