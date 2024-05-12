import { useCustomMutation, useApiUrl } from '@refinedev/core'
import confetti from 'canvas-confetti'

type TAddToCart = {
  product_id: string
  quantity: number
  variation_id: string
}

const defaultProps = {
  enableConfetti: true,
  onMutate: () => {},
  onSuccess: () => {},
  onError: () => {},
  onSettled: () => {},
}

export type TUseAddToCartParams = Partial<typeof defaultProps>

export const useAddToCart = (props?: TUseAddToCartParams) => {
  const { enableConfetti, onMutate, onSuccess, onError, onSettled } = {
    ...defaultProps,
    ...props,
  }
  const mutation = useCustomMutation({
    mutationOptions: {
      onMutate: () => {
        if (enableConfetti) {
          doConfetti()
        }
        onMutate()
      },
      onSettled: () => {
        // 刷新 WC 購物車

        ;(window as any)?.jQuery('body')?.trigger('wc_fragment_refresh')
        onSettled()
      },
    },
  })

  const { mutate } = mutation

  const apiUrl = useApiUrl()

  const addToCart = ({ product_id, quantity, variation_id }: TAddToCart) => {
    mutate(
      {
        url: `${apiUrl}/cart`,
        method: 'post',
        values: {
          product_id,
          quantity,
          variation_id,
        },
      },
      {
        onSuccess: () => {
          onSuccess()
        },
        onError: (error) => {
          onError()
        },
      },
    )
  }

  return {
    addToCart,
    mutation,
  }
}

function doConfetti() {
  const defaultArgs = {
    particleCount: 150,
    scalar: 0.75,
    ticks: 60,
    startVelocity: 70,
    spread: 360,
    origin: { x: randomInRange(0.1, 0.9), y: randomInRange(-0.2, 0.8) },
  }
  for (let index = 0; index < 3; index++) {
    confetti(defaultArgs)
  }
}

function randomInRange(min: number, max: number) {
  return Math.random() * (max - min) + min
}
