import React, { memo } from 'react'
import Bundle from './Bundle'
import { Form } from 'antd'

const BundleTypes = () => {
	const bundleProductForm = Form.useFormInstance()
	const watchBundleType = Form.useWatch(['bundle_type'], bundleProductForm)

	return <Bundle />
}

export default memo(BundleTypes)
