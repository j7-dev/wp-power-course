import React, { useState } from 'react'
import { MediaLibrary, TAttachment, TImage } from 'antd-toolkit/wp'

const MediaLibraryPage = () => {
	const [selectedItems, setSelectedItems] = useState<(TAttachment | TImage)[]>(
		[],
	)
	return (
		<MediaLibrary
			selectedItems={selectedItems}
			setSelectedItems={setSelectedItems}
			limit={undefined}
		/>
	)
}

export default MediaLibraryPage
