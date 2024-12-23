import React from 'react'
import Simple from './Simple'
import Subscription from './Subscription'
import { Form } from 'antd'

const ProductPriceFields = () => {
	const bundleProductForm = Form.useFormInstance()
	const watchProductType = Form.useWatch(['type'], bundleProductForm)

	if ('subscription' === watchProductType) {
		return <Subscription />
	}

	return <Simple />
}

export default ProductPriceFields
