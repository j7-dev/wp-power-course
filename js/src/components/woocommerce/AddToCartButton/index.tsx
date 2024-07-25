import { FC } from 'react'
import { Button, ButtonProps } from 'antd'
import { useAddToCart, TUseAddToCartParams } from './useAddToCart'

type TAddToCartButtonProps = {
	product_id: string
	quantity: number
	variation_id: string
} & ButtonProps & {
		useAddToCartParams?: TUseAddToCartParams
	}

export const AddToCartButton: FC<TAddToCartButtonProps> = ({
	product_id,
	quantity,
	variation_id,
	children = '加入購物車',
	useAddToCartParams,
	...buttonProps
}) => {
	const { addToCart, mutation } = useAddToCart(useAddToCartParams)

	const { isLoading } = mutation

	const handleClick = () => {
		addToCart({ product_id, quantity, variation_id })
	}

	return (
		<>
			<Button
				type="primary"
				className="w-full"
				onClick={handleClick}
				loading={isLoading}
				htmlType="button"
				{...buttonProps}
			>
				{children}
			</Button>
		</>
	)
}

export * from './useAddToCart'
