import React from 'react'
import { useMediaUpload } from '@/pages/admin/MediaLibrary/hooks'
import { Upload } from '@/components/general'

const UploadVideo = ({
	setActiveKey,
}: {
	setActiveKey: React.Dispatch<React.SetStateAction<string>>
}) => {
	const bunnyUploadProps = useMediaUpload({
		setActiveKey,
	})
	return (
		<>
			<Upload {...bunnyUploadProps} />
		</>
	)
}

export default UploadVideo
