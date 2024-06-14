import { useState } from 'react'
import { GetProp, UploadFile, UploadProps } from 'antd'

type FileType = Parameters<GetProp<UploadProps, 'beforeUpload'>>[0]

/**
 * accept: string
 * @example '.jpg,.png' , 'image/*', 'video/*', 'audio/*', 'image/png,image/jpeg', '.pdf, .docx, .doc, .xml'
 * @see accept https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file#unique_file_type_specifiers
 */

type TUseUploadParams = {
  uploadProps?: UploadProps
}

export const useUpload = (props?: TUseUploadParams) => {
  const uploadProps = props?.uploadProps

  /**
   * fileList: UploadFile[]
   * @example
   * [
   *    {
   *     uid: '-1',
   *     name: 'image.png',
   *     status: 'done',
   *     url: 'https://zos.alipayobjects.com/rmsportal/jkjgkEfvpUPVyRjUImniVslZfWPnJuuZ.png',
   *   },
   *  ]
   */

  const [fileList, setFileList] = useState<UploadFile[]>([])

  const mergedUploadProps: UploadProps = {
    accept: 'image/*',
    multiple: true, // 是否支持多選文件，ie10+ 支持。按住 ctrl 多選文件
    maxCount: 2, // 最大檔案數
    // onPreview: async (file: UploadFile) => {
    //   let src = file.url as string
    //   if (!src) {
    //     src = await new Promise((resolve) => {
    //       const reader = new FileReader()
    //       reader.readAsDataURL(file.originFileObj as FileType)
    //       reader.onload = () => resolve(reader.result as string)
    //     })
    //   }
    //   const image = new Image()
    //   image.src = src
    //   const imgWindow = window.open(src)
    //   imgWindow?.document.write(image.outerHTML)
    // },

    beforeUpload: (file, theFileList) => {
      setFileList([...fileList, ...theFileList])

      return false
    },
    onRemove: (file) => {
      const index = fileList.indexOf(file)
      const newFileList = fileList.slice()
      newFileList.splice(index, 1)
      setFileList(newFileList)
    },
    listType: 'picture',
    fileList,
    ...uploadProps,
  }

  return { uploadProps: mergedUploadProps, fileList, setFileList }
}
