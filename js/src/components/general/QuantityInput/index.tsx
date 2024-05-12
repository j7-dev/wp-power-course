import { FC } from 'react'
import { InputNumber, InputNumberProps } from 'antd'
import './style.scss'

export const QuantityInput: FC<
  InputNumberProps<number> & {
    stockQty?: number | null // stockQty is the maximum value of the input
  }
> = ({ stockQty, ...inputNumberProps }) => {
  const getProps = (v: number | undefined | null) => {
    if (v === undefined || v === null) return inputNumberProps

    if (v >= 0) {
      return {
        ...inputNumberProps,
        max: v,
      }
    }
    return inputNumberProps
  }
  const props = getProps(stockQty)

  return <InputNumber {...props} />
}

export * from './useQuantityInput'
