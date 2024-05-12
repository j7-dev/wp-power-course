import { useState } from 'react'
import { DrawerProps } from 'antd'

export const useCourseDrawer = () => {
  const [open, setOpen] = useState(false)

  const show = () => {
    setOpen(true)
  }

  const close = () => {
    setOpen(false)
  }

  const drawerProps: DrawerProps = {
    title: '新增課程',
    onClose: close,
    open,
    width: '70%',
  }

  return {
    open,
    setOpen,
    show,
    close,
    drawerProps,
  }
}
