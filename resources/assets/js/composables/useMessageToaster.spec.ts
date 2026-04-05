import { describe, expect, it, vi } from 'vite-plus/test'

const successMock = vi.fn()
const infoMock = vi.fn()
const warningMock = vi.fn()
const errorMock = vi.fn()

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  requireInjection: () => ({
    value: {
      success: successMock,
      info: infoMock,
      warning: warningMock,
      error: errorMock,
    },
  }),
}))

import { useMessageToaster } from './useMessageToaster'

describe('useMessageToaster', () => {
  it('exposes toast methods', () => {
    const { toastSuccess, toastInfo, toastWarning, toastError } = useMessageToaster()

    const successMsg = 'Done!'
    const infoMsg = 'FYI'
    const warningMsg = 'Careful'
    const errorMsg = 'Oops'

    toastSuccess(successMsg)
    expect(successMock).toHaveBeenCalledWith(successMsg)

    toastInfo(infoMsg)
    expect(infoMock).toHaveBeenCalledWith(infoMsg)

    toastWarning(warningMsg)
    expect(warningMock).toHaveBeenCalledWith(warningMsg)

    toastError(errorMsg)
    expect(errorMock).toHaveBeenCalledWith(errorMsg)
  })
})
