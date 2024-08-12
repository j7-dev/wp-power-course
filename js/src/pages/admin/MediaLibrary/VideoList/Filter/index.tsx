import React, { useState } from 'react'
import { Input, InputProps } from 'antd'

const { Search } = Input

const Filter = ({
	setSearch,
	loading,
	...inputProps
}: {
	setSearch: React.Dispatch<React.SetStateAction<string>>
	loading?: boolean
} & InputProps) => {
	const [value, setValue] = useState('')
	return (
		<>
			<Search
				placeholder="搜尋關鍵字"
				className="w-60 mb-4"
				value={value}
				onChange={(e) => setValue(e.target.value)}
				allowClear
				onSearch={() => setSearch(value)}
				enterButton
				loading={loading}
				{...inputProps}
			/>
		</>
	)
}

export default Filter
