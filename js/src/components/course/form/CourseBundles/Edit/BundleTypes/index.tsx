import React from 'react'
import Bundle from './Bundle'
import Subscription from './Subscription'
import { Form } from 'antd'

const BundleTypes = () => {
	const bundleProductForm = Form.useFormInstance()
	const watchProductType = Form.useWatch(['product_type'], bundleProductForm)

	if ('subscription' === watchProductType) {
		return <Subscription />
	}

	return <Bundle />
}

export default BundleTypes
