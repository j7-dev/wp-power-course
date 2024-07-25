import { useState } from 'react'
import { InputNumberProps } from 'antd'

type TUseQuantityInput = (props?: { max?: number; min?: number }) => {
	quantityInputProps: InputNumberProps<number>
	setQuantityInputProps: React.Dispatch<React.SetStateAction<InputNumberProps>>
}

const defaultParams = {
	max: Infinity,
	min: 1,
}

export const useQuantityInput: TUseQuantityInput = (
	params?: Partial<typeof defaultParams>,
) => {
	const { max, min } = {
		...defaultParams,
		...params,
	}
	const [
		quantityInputProps,
		setQuantityInputProps,
	] = useState<InputNumberProps>({
		value: 1,
		defaultValue: 1,
	})

	const handleMinus = () => {
		const value = Number(quantityInputProps?.value || 1)
		if (value <= min) return
		setQuantityInputProps({
			...quantityInputProps,
			value: value - 1,
		})
	}

	const handlePlus = () => {
		const value = Number(quantityInputProps?.value || 1)
		if (value >= max) return
		setQuantityInputProps({
			...quantityInputProps,
			value: value + 1,
		})
	}

	const handleChange = (v: number | null) => {
		if (v) {
			setQuantityInputProps({
				...quantityInputProps,
				value: v,
			})
		} else {
			setQuantityInputProps({
				...quantityInputProps,
				value: 1,
			})
		}
	}

	return {
		quantityInputProps: {
			...quantityInputProps,
			min,
			className: 'w-full',
			addonBefore: (
				<span className="at-addon" onClick={handleMinus}>
					-
				</span>
			),
			addonAfter: (
				<span className="at-addon" onClick={handlePlus}>
					+
				</span>
			),
			onChange: handleChange,
		} as InputNumberProps<number>,
		setQuantityInputProps,
	}
}
