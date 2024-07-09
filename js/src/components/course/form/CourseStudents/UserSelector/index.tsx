import { useSelect } from '@refinedev/antd'
import { Select, Space, Button, Form } from 'antd'
import { TUserRecord } from '@/pages/admin/Courses/CourseSelector/types'

const index = () => {
  const form = Form.useFormInstance()
  const watchId = Form.useWatch(['id'], form)

  const { selectProps } = useSelect<TUserRecord>({
    resource: 'users',
    optionLabel: 'display_name',
    optionValue: 'id',
    filters: [
      {
        field: 'search',
        operator: 'eq',
        value: '',
      },
      {
        field: 'number',
        operator: 'eq',
        value: '20',
      },
    ],
    onSearch: (value) => [
      {
        field: 'search',
        operator: 'eq',
        value,
      },
    ],
    queryOptions: {
      enabled: !!watchId,
    },
  })

  return (
    <Space.Compact className="w-full">
      <Button type="primary">新增學員</Button>
      <Select
        {...selectProps}
        className="w-full"
        placeholder="試試看搜尋 Email, 名稱, ID"
        mode="multiple"
        allowClear
      />
    </Space.Compact>
  )
}

export default index
