import { TProduct as TProductStoreApi } from '@/types/wcStoreApi'
import { TProduct as TProductRestApi } from '@/types/wcRestApi'
import defaultImage from '@/assets/images/defaultImage.jpg'

export const getProductImageSrc = (
	product: TProductStoreApi | TProductRestApi,
) => {
	const images = product?.images ?? []
	const image = images[0] ?? {}
	const imageSrc = image?.src ?? defaultImage
	return imageSrc
}

export const getBundleType = (bundleType: string) => {
	switch (bundleType) {
		case 'subscription':
			return {
				label: '定期定額',
				color: 'purple',
			}
		case 'bundle':
			return {
				label: '合購方案',
				color: 'cyan',
			}
		default:
			return {
				label: bundleType,
				color: 'default',
			}
	}
}
