import React from 'react'
import Bundle from './Bundle'
import Subscription from './Subscription'
import { Form } from 'antd'

const BundleTypes = () => {
	const bundleProductForm = Form.useFormInstance()
	const watchBundleType = Form.useWatch(['bundle_type'], bundleProductForm)

	if ('subscription' === watchBundleType) {
		return <Subscription />
	}

	return <Bundle />
}

export default BundleTypes
