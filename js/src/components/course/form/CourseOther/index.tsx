import React from 'react'
import { Form, Switch, Slider, InputNumber, Rate, Tooltip, Select } from 'antd'
import { Heading } from '@/components/general'
import { FiSwitch } from '@/components/formItem'

const { Item } = Form

export const CourseOther = () => {
  const form = Form.useFormInstance()

  const watchIsPopular: boolean = Form.useWatch(['is_popular'], form) === 'yes'
  const watchIsFeatured: boolean =
    Form.useWatch(['is_featured'], form) === 'yes'

  const watchAvgRating: number = Form.useWatch(['average_rating'], form)
  const watchReviewCount: number = Form.useWatch(['review_count'], form)
  const watchShowReview: boolean =
    Form.useWatch(['show_review'], form) === 'yes'

  return (
    <>
      <Heading>課程標籤(非真標籤，僅外觀顯示)</Heading>
      <div className="grid 2xl:grid-cols-3 gap-6 mb-[4.5rem]">
        <div className="flex gap-4 items-end">
          <FiSwitch
            formItemProps={{
              name: ['is_popular'],
              label: '顯示熱門課程標籤',
              className: 'mb-0',
            }}
          />
          <span className="bg-red-100 [&_svg]:fill-red-500 text-red-500 text-xs flex items-center px-2 py-1 rounded-md w-fit h-fit mb-1">
            <svg
              className="w-4 h-4 mr-1"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <g strokeWidth="0"></g>
              <g strokeLinecap="round" strokeLinejoin="round"></g>
              <g>
                {' '}
                <path d="M12.8324 21.8013C15.9583 21.1747 20 18.926 20 13.1112C20 7.8196 16.1267 4.29593 13.3415 2.67685C12.7235 2.31757 12 2.79006 12 3.50492V5.3334C12 6.77526 11.3938 9.40711 9.70932 10.5018C8.84932 11.0607 7.92052 10.2242 7.816 9.20388L7.73017 8.36604C7.6304 7.39203 6.63841 6.80075 5.85996 7.3946C4.46147 8.46144 3 10.3296 3 13.1112C3 20.2223 8.28889 22.0001 10.9333 22.0001C11.0871 22.0001 11.2488 21.9955 11.4171 21.9858C10.1113 21.8742 8 21.064 8 18.4442C8 16.3949 9.49507 15.0085 10.631 14.3346C10.9365 14.1533 11.2941 14.3887 11.2941 14.7439V15.3331C11.2941 15.784 11.4685 16.4889 11.8836 16.9714C12.3534 17.5174 13.0429 16.9454 13.0985 16.2273C13.1161 16.0008 13.3439 15.8564 13.5401 15.9711C14.1814 16.3459 15 17.1465 15 18.4442C15 20.4922 13.871 21.4343 12.8324 21.8013Z"></path>{' '}
              </g>
            </svg>
            熱門課程
          </span>
        </div>
        <div className="flex gap-4 items-end">
          <FiSwitch
            formItemProps={{
              name: ['is_featured'],
              label: '顯示精選標籤',
              className: 'mb-0',
            }}
          />
          <span className="bg-amber-100 [&_svg_path]:fill-amber-500 text-amber-500 text-xs flex items-center px-2 py-1 rounded-md w-fit h-fit mb-1">
            <svg
              className="w-4 h-4 mr-1"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <g strokeWidth="0"></g>
              <g strokeLinecap="round" strokeLinejoin="round"></g>
              <g>
                <path
                  opacity="0.5"
                  d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z"
                ></path>{' '}
                <path d="M10.4127 8.49812L10.5766 8.20419C11.2099 7.06807 11.5266 6.5 12 6.5C12.4734 6.5 12.7901 7.06806 13.4234 8.20419L13.5873 8.49813C13.7672 8.82097 13.8572 8.98239 13.9975 9.0889C14.1378 9.19541 14.3126 9.23495 14.6621 9.31402L14.9802 9.38601C16.2101 9.66428 16.825 9.80341 16.9713 10.2739C17.1176 10.7443 16.6984 11.2345 15.86 12.215L15.643 12.4686C15.4048 12.7472 15.2857 12.8865 15.2321 13.0589C15.1785 13.2312 15.1965 13.4171 15.2325 13.7888L15.2653 14.1272C15.3921 15.4353 15.4554 16.0894 15.0724 16.3801C14.6894 16.6709 14.1137 16.4058 12.9622 15.8756L12.6643 15.7384C12.337 15.5878 12.1734 15.5124 12 15.5124C11.8266 15.5124 11.663 15.5878 11.3357 15.7384L11.0378 15.8756C9.88633 16.4058 9.31059 16.6709 8.92757 16.3801C8.54456 16.0894 8.60794 15.4353 8.7347 14.1272L8.76749 13.7888C8.80351 13.4171 8.82152 13.2312 8.76793 13.0589C8.71434 12.8865 8.59521 12.7472 8.35696 12.4686L8.14005 12.215C7.30162 11.2345 6.88241 10.7443 7.02871 10.2739C7.17501 9.80341 7.78994 9.66427 9.01977 9.38601L9.33794 9.31402C9.68743 9.23495 9.86217 9.19541 10.0025 9.0889C10.1428 8.98239 10.2328 8.82097 10.4127 8.49812Z"></path>{' '}
              </g>
            </svg>
            精選課程
          </span>
        </div>
      </div>

      <Heading>課程評價</Heading>

      <div className="grid 2xl:grid-cols-3 gap-6 mb-12">
        <FiSwitch
          formItemProps={{
            name: ['show_review'],
            label: '顯示用戶評價',
          }}
        />
        <FiSwitch
          formItemProps={{
            name: ['enable_review'],
            label: '開放已購買用戶評價課程',
          }}
        />
        {watchShowReview && (
          <>
            <Item
              label="自訂課程評價"
              name={['average_rating']}
              initialValue={2.5}
            >
              <Slider step={0.1} min={0} max={5} />
            </Item>
            <div>
              <p className="mb-2">預覽</p>
              <Tooltip title="預覽" className="w-fit mb-12">
                <div className="flex items-center text-gray-800">
                  <span className="mr-2 text-2xl font-semibold">
                    {Number(watchAvgRating).toFixed(1)}
                  </span>
                  <Rate disabled value={watchAvgRating} allowHalf />
                  <span className="ml-2">({watchReviewCount || 0})</span>
                </div>
              </Tooltip>
            </div>
            <Item
              label="自訂評價數量"
              name={['review_count']}
              initialValue={20}
            >
              <InputNumber className="w-full" min={0} />
            </Item>
            <Item
              label="灌水學員人數"
              name={['extra_student_count']}
              tooltip="前台顯示學員人數 = 實際學員人數 + 灌水學員人數"
              initialValue={0}
            >
              <InputNumber
                addonBefore="實際學員人數 + "
                addonAfter="人"
                className="w-full"
                min={0}
              />
            </Item>
          </>
        )}
      </div>

      <Heading>課程留言</Heading>

      <div className="grid 2xl:grid-cols-3 gap-6 mb-12">
        <FiSwitch
          formItemProps={{
            name: ['enable_comment'],
            label: '開放用戶留言',
          }}
        />
      </div>
    </>
  )
}
