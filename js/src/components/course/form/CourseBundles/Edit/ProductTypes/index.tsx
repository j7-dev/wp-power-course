import React from 'react'
import Simple from './Simple'
import Subscription from './Subscription'
import { Form } from 'antd'

const ProductTypes = ({
	bundlePrices,
}: {
	bundlePrices: { regular_price: React.ReactNode; sale_price: React.ReactNode }
}) => {
	const bundleProductForm = Form.useFormInstance()
	const watchProductType = Form.useWatch(['type'], bundleProductForm)

	if ('subscription' === watchProductType) {
		return <Subscription />
	}

	return <Simple bundlePrices={bundlePrices} />
}

export default ProductTypes
