import { Dayjs } from 'dayjs'

export * from './user'

export type TFilterProps = Partial<{
  s: string
  sku: string
  product_category_id?: string[]
  product_tag_id?: string[]
  product_brand_id?: string[]
  status: string
  featured: boolean
  downloadable: boolean
  virtual: boolean
  sold_individually: boolean
  backorders: string
  stock_status: string
  date_created: [Dayjs, Dayjs]
  is_course: boolean
  price_range: [number, number]
}>

export type TTerm = {
  id: string
  name: string
}
export type TStockStatus = 'instock' | 'outofstock' | 'onbackorder'
export type TProductType =
  | 'simple'
  | 'variable'
  | 'grouped'
  | 'external'
  | 'subscription'
  | 'variable-subscription'
  | string

export type TProductAttribute = {
  name: string
  options: string[]
  position: number
}

type TImage = {
  id: string
  url: string
}

export type TCourseRecord = {
  id: string
  type: TProductType
  depth: number
  name: string
  slug: string
  date_created: string
  date_modified: string
  status: string
  featured: boolean
  catalog_visibility: string
  description: string
  short_description: string
  sku: string
  menu_order: number
  virtual: boolean
  downloadable: boolean
  permalink: string
  average_rating: number
  review_count: number
  price_html: string
  regular_price: string
  sale_price: string
  on_sale: boolean
  date_on_sale_from: string | null
  date_on_sale_to: string | null
  total_sales: number
  stock: number | null
  stock_status: TStockStatus
  manage_stock: boolean
  stock_quantity: number | null
  backorders: 'yes'
  backorders_allowed: boolean
  backordered: boolean
  low_stock_amount: number | null
  upsell_ids: number[]
  cross_sell_ids: number[]
  attributes: TProductAttribute[]
  category_ids: string[]
  tag_ids: string[]
  images: TImage[]
  chapters?: TChapterRecord[]
  is_course: boolean
  parent_id?: string
  hours: number
  is_free: 'yes' | 'no' | ''
  feature_video: string
  trial_video: string
  bundle_ids: string[]
}

export type TChapterRecord = {
  id: string
  type: 'chapter'
  status: string
  depth: number
  name: string
  slug?: string
  date_created?: string
  date_modified?: string
  catalog_visibility?: string
  description?: string
  short_description?: string
  sku?: undefined
  menu_order?: number
  total_sales?: undefined
  permalink?: string
  category_ids?: string[]
  tag_ids?: string[]
  images?: TImage[]
  chapters?: TChapterRecord[]
  parent_id?: string
  bunny_video_id?: string
}
