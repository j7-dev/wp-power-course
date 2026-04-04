import { atom } from 'jotai'

import { TBundleProductRecord } from '@/components/product/ProductTable/types'
import { TCourseRecord } from '@/pages/admin/Courses/List/types'

export const selectedProductsAtom = atom<TBundleProductRecord[]>([])

export const courseAtom = atom<TCourseRecord | undefined>(undefined)

export const bundleProductAtom = atom<TBundleProductRecord | undefined>(
	undefined
)

/**
 * 管理銷售方案各商品的數量
 * key 為商品 ID（string），value 為數量（1~999）
 */
export const productQuantitiesAtom = atom<Record<string, number>>({})
