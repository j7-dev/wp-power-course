import { useEffect, useState, FC } from 'react'
import {
  Form,
  InputNumber,
  DatePicker,
  Select,
  Input,
  FormInstance,
  List,
  Tag,
} from 'antd'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import dayjs from 'dayjs'
import { TProductRecord } from '@/pages/admin/Courses/ProductSelector/types'
import defaultImage from '@/assets/images/defaultImage.jpg'
import { renderHTML } from 'antd-toolkit'
import { useList } from '@refinedev/core'
import { PopconfirmDelete } from '@/components/general'
import {
  CheckOutlined,
  PlusOutlined,
  ExclamationCircleOutlined,
} from '@ant-design/icons'
import { selectedRecordAtom } from '@/pages/admin/Courses/CourseSelector'
import { useAtomValue } from 'jotai'
import { TCourseRecord } from '@/pages/admin/Courses/CourseSelector/types'
import { FiSwitch } from '@/components/formItem'
import { useUpload } from '@/bunny'
import { FileUpload } from '@/components/post'

// TODO 目前只支援簡單商品
// TODO 如何結合可變商品?

dayjs.extend(customParseFormat)

const { RangePicker } = DatePicker

const { Item } = Form
const { Search } = Input

const OPTIONS = [
  { label: '合購優惠', value: 'bundle' },
  { label: '團購優惠', value: 'groupbuy', disabled: true },
]

const INCLUDED_PRODUCT_IDS_FIELD_NAME = 'pbp_product_ids' // 包含商品的 ids

const BundleForm: FC<{
  form: FormInstance
  open: boolean
}> = ({ form: bundleProductForm, open }) => {
  const selectedCourse = useAtomValue(selectedRecordAtom) as TCourseRecord

  const watchRegularPrice = Form.useWatch(['regular_price'], bundleProductForm)
  const watchId = Form.useWatch(['id'], bundleProductForm)

  const [selectedProducts, setSelectedProducts] = useState<TProductRecord[]>([])
  const [searchKeyWord, setSearchKeyWord] = useState<string>('')
  const [showList, setShowList] = useState<boolean>(false)

  const bunnyUploadProps = useUpload()
  const { fileList } = bunnyUploadProps

  const onSearch = (value: string) => {
    setSearchKeyWord(value)
  }

  const searchProductsResult = useList<TProductRecord>({
    resource: 'products',
    filters: [
      {
        field: 's',
        operator: 'eq',
        value: searchKeyWord,
      },
      {
        field: 'status',
        operator: 'eq',
        value: 'publish',
      },
      {
        field: 'posts_per_page',
        operator: 'eq',
        value: '20',
      },
      {
        field: 'exclude',
        operator: 'eq',
        value: [selectedCourse?.id],
      },
      {
        field: 'product_type',
        operator: 'eq',
        value: 'simple',
      },
    ],
    queryOptions: {
      staleTime: 1000 * 60 * 60 * 24,
      cacheTime: 1000 * 60 * 60 * 24,
    },
  })

  const searchProducts = searchProductsResult.data?.data || []

  // 處理點擊商品，有可能是加入也可能是移除

  const handleClick = (product: TProductRecord) => () => {
    const isInclude = selectedProducts.some(({ id }) => id === product.id)
    if (isInclude) {
      // 當前列表中已經有這個商品，所以要移除

      setSelectedProducts(
        selectedProducts.filter(({ id }) => id !== product.id),
      )
    } else {
      // 當前列表中沒有這個商品，所以要加入

      setSelectedProducts([...selectedProducts, product])
    }
  }

  useEffect(() => {
    bundleProductForm.setFieldValue(['files'], fileList)
  }, [fileList])

  useEffect(() => {
    // 選擇商品改變時，同步更新到表單上

    bundleProductForm.setFieldValue(
      [INCLUDED_PRODUCT_IDS_FIELD_NAME],
      [
        selectedCourse?.id,
        ...selectedProducts.map(({ id }) => id),
      ],
    )

    bundleProductForm.setFieldValue(
      ['regular_price'],
      getPrice({
        type: 'regular_price',
        products: selectedProducts,
        selectedCourse,
      }),
    )
  }, [selectedProducts.length])

  useEffect(() => {
    if (open) {
      setSelectedProducts([])
    }
  }, [open])

  // 如果是編輯，要將 included 商品資料顯示在畫面上

  const watchIncludedProductIds = Form.useWatch(
    [INCLUDED_PRODUCT_IDS_FIELD_NAME],
    bundleProductForm,
  ) as string[]

  // 將當前商品移除

  const includedProductIds =
    watchIncludedProductIds?.filter((id) => id !== selectedCourse?.id) || []

  const { data: includedProductsData, isFetching: IPIsFetching } =
    useList<TProductRecord>({
      resource: 'products',
      filters: [
        {
          field: 'include',
          operator: 'eq',
          value: includedProductIds,
        },
      ],
      queryOptions: {
        enabled: !!watchId && !!includedProductIds.length,
      },
    })

  const includedProducts = includedProductsData?.data || []

  useEffect(() => {
    // 有 id = 編輯模式，要將資料填入表單

    if (!!watchId && !!includedProductIds.length && !IPIsFetching) {
      setSelectedProducts(includedProducts)
    }
  }, [watchId, IPIsFetching])

  return (
    <Form form={bundleProductForm} layout="vertical">
      <Item name={['id']} hidden normalize={() => undefined}>
        <Input />
      </Item>
      <Item
        name={['bundle_type']}
        label="銷售方案種類"
        initialValue={OPTIONS[0].value}
      >
        <Select options={OPTIONS} />
      </Item>
      <Item name={['name']} label="銷售方案名稱">
        <Input />
      </Item>
      <Item
        name={['description']}
        label="銷售方案說明"
        normalize={(value) => value.replace(/\n/g, '<br>')}
      >
        <Input.TextArea rows={8} />
      </Item>

      <Item
        name={[INCLUDED_PRODUCT_IDS_FIELD_NAME]}
        className="mb-0 -mt-8"
        rules={[
          {
            required: true,
            message: (
              <>
                <ExclamationCircleOutlined className="mr-2" />
                請至少加入一款產品
              </>
            ),
          },
          {
            len: 2,
            type: 'array',
            message: (
              <>
                <ExclamationCircleOutlined className="mr-2" />
                請至少加入一款產品
              </>
            ),
          },
        ]}
      >
        <Select className="tw-hidden" mode="multiple" options={[]} />
      </Item>

      <p className="mb-3">搭配你的銷售方案，請選擇要加入的商品</p>
      <div className="border-2 border-dashed border-blue-500 rounded-xl p-4 mb-8">
        {/* 當前課程方案 */}
        <div className="flex items-center justify-between gap-4 border border-solid border-gray-200 p-2 rounded-md">
          <img
            src={selectedCourse?.images?.[0]?.url || defaultImage}
            className="h-9 w-16 rounded object-cover"
          />
          <div className="w-full">
            {selectedCourse?.name} #{selectedCourse?.id}{' '}
            {renderHTML(selectedCourse?.price_html || '')}
          </div>
          <div>
            <Tag color="blue">目前課程</Tag>
          </div>
        </div>
        {/* END 當前課程方案 */}
        <div className="text-center my-2">
          <PlusOutlined />
        </div>
        <div className="relative mb-2">
          <Search
            placeholder="請輸入關鍵字後按下 ENTER 搜尋，每次最多返回 20 筆資料"
            allowClear
            onSearch={onSearch}
            enterButton
            loading={searchProductsResult.isFetching}
            onClick={() => setShowList(!showList)}
          />
          <div
            className={`absolute border border-solid border-gray-200 rounded-md shadow-lg top-[100%] w-full bg-white z-50 h-[30rem] overflow-y-auto ${showList ? 'block' : 'tw-hidden'}`}
            onMouseLeave={() => setShowList(false)}
          >
            <List
              rowKey="id"
              dataSource={searchProducts}
              renderItem={(product) => {
                const { id, images, name, price_html } = product
                const isInclude = selectedProducts.some(
                  ({ id: theId }) => theId === product.id,
                )
                return (
                  <div
                    key={id}
                    className={`flex items-center justify-between gap-4 p-2 mb-0 cursor-pointer hover:bg-blue-100 ${isInclude ? 'bg-blue-100' : 'bg-white'}`}
                    onClick={handleClick(product)}
                  >
                    <img
                      src={images?.[0]?.url || defaultImage}
                      className="h-9 w-16 rounded object-cover"
                    />
                    <div className="w-full">
                      {name} #{id} {renderHTML(price_html)}
                    </div>
                    <div className="w-8 text-center">
                      {isInclude && <CheckOutlined className="text-blue-500" />}
                    </div>
                  </div>
                )
              }}
            />
          </div>
        </div>

        {!IPIsFetching &&
          selectedProducts?.map(({ id, images, name, price_html }) => (
            <div
              key={id}
              className="flex items-center justify-between gap-4 border border-solid border-gray-200 p-2 rounded-md mb-2"
            >
              <div className="rounded aspect-video w-16 overflow-hidden">
                <img
                  src={images?.[0]?.url || defaultImage}
                  className="w-full h-full rounded object-cover"
                />
              </div>
              <div className="flex-1">
                {name} #{id} {renderHTML(price_html)}
              </div>
              <div className="w-8 text-right">
                <PopconfirmDelete
                  popconfirmProps={{
                    onConfirm: () => {
                      setSelectedProducts(
                        selectedProducts?.filter(
                          ({ id: productId }) => productId !== id,
                        ),
                      )
                    },
                  }}
                />
              </div>
            </div>
          ))}

        {/* Loading */}
        {IPIsFetching &&
          includedProductIds.map((id) => (
            <div
              key={id}
              className="flex items-center justify-start gap-4 border border-solid border-gray-200 p-2 rounded-md mb-2 animate-pulse"
            >
              <div className="bg-slate-300 h-9 w-16 rounded object-cover" />
              <div>
                <div className="bg-slate-300 h-3 w-20 mb-1" />
                <div className="bg-slate-300 h-3 w-32" />
              </div>
            </div>
          ))}
      </div>

      <Item name={['regular_price']} label="此銷售組合原價" hidden>
        <InputNumber
          addonBefore="NT$"
          className="w-full [&_input]:text-right [&_.ant-input-number]:bg-white [&_.ant-input-number-group-addon]:bg-[#fafafa]  [&_.ant-input-number-group-addon]:text-[#1f1f1f]"
          min={0}
          disabled
        />
      </Item>
      <Item
        name={['sale_price']}
        label="方案折扣價"
        tooltip="折扣價不能超過原價"
        rules={[
          {
            type: 'number',
            max: watchRegularPrice,
            message: '折扣價不能超過原價',
          },
        ]}
        help={
          <>
            <div className="grid grid-cols-2 gap-x-4 mb-4">
              <div>此銷售組合原訂原價</div>
              <div className="text-right pr-0">
                {getPrice({
                  isFetching: IPIsFetching,
                  type: 'regular_price',
                  products: selectedProducts,
                  selectedCourse,
                  returnType: 'string',
                })}
              </div>
              <div>此銷售組合原訂折扣價</div>
              <div className="text-right pr-0">
                {getPrice({
                  isFetching: IPIsFetching,
                  type: 'sale_price',
                  products: selectedProducts,
                  selectedCourse,
                  returnType: 'string',
                })}
              </div>
            </div>
          </>
        }
      >
        <InputNumber
          addonBefore="NT$"
          className="w-full [&_input]:text-right"
          min={0}
          controls={false}
        />
      </Item>

      <Item
        name={['sale_date_range']}
        label="銷售期間"
        getValueProps={(value) => ({
          value: [
            value?.[0] ? dayjs.unix(value[0]) : null,
            value?.[1] ? dayjs.unix(value[1]) : null,
          ],
        })}
      >
        <RangePicker
          className="w-full"
          showTime={{
            defaultValue: [
              dayjs('00:00', 'HH:mm'),
              dayjs('11:59', 'HH:mm'),
            ],
          }}
          allowEmpty={[true, true]}
          format="YYYY-MM-DD HH:mm"
        />
      </Item>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <p className="mb-3">課程封面圖</p>
          <div className="mb-8">
            <FileUpload />
            <Item hidden name={['files']} label="課程封面圖">
              <Input />
            </Item>
            <Item hidden name={['images']}>
              <Input />
            </Item>
          </div>
        </div>
      </div>

      <FiSwitch
        formItemProps={{
          name: ['status'],
          label: '發佈',
          initialValue: 'publish',
          getValueProps: (value) => ({ value: value === 'publish' }),
          normalize: (value) => (value ? 'publish' : 'draft'),
        }}
        switchProps={{
          checkedChildren: '發佈',
          unCheckedChildren: '草稿',
        }}
      />
    </Form>
  )
}

function getPrice({
  isFetching = false,
  type,
  products,
  selectedCourse,
  returnType = 'number',
}: {
  isFetching?: boolean
  type: 'regular_price' | 'sale_price'
  products: TProductRecord[] | undefined
  selectedCourse: TCourseRecord | undefined
  returnType?: 'string' | 'number'
}) {
  if (isFetching) {
    return <div className="w-20 bg-slate-300 animate-pulse h-3 inline-block" />
  }

  const coursePrice = Number(
    selectedCourse?.[type] || selectedCourse?.regular_price || 0,
  )
  const total =
    Number(
      products?.reduce(
        (acc, product) =>
          acc + Number(product?.[type] || product.regular_price),
        0,
      ),
    ) + coursePrice

  if ('number' === returnType) return total
  return `NT$ ${total?.toLocaleString()}`
}

export default BundleForm
