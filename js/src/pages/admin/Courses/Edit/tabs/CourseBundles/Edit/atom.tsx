import { atom } from 'jotai'

import { TBundleProductRecord } from '@/components/product/ProductTable/types'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'

export const selectedProductsAtom = atom<TBundleProductRecord[]>([])

/**
 * 銷售方案中每個商品的數量映射
 * key: product_id (string), value: quantity (number)
 */
export const productQuantitiesAtom = atom<Record<string, number>>({})

export const courseAtom = atom<TCourseRecord | undefined>(undefined)

export const bundleProductAtom = atom<TBundleProductRecord | undefined>(
	undefined
)
