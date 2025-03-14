/** @type {import('tailwindcss').Config} */
// eslint-disable-next-line no-undef
module.exports = {
	important: '#tw',
	corePlugins: {
		preflight: false,
	},
	content: [
		'./js/src/**/*.{js,ts,jsx,tsx}',
		'./inc/**/*.php',
		'./inc/assets/src/**/*.ts',
	],
	theme: {
		extend: {
			animation: {
				pulse: 'tw-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
			},
			screens: {
				sm: '576px', // iphone SE
				md: '810px', // ipad Portrait
				lg: '1080px', // ipad Landscape
				xl: '1280px', // mac air
				xxl: '1440px',
			},
			keyframes: {
				'tw-pulse': {
					'50%': { opacity: '0.5' },
				},
			},
		},
	},
	plugins: [
		require('daisyui'),
		function ({ addUtilities }) {
			const newUtilities = {
				'.right-to-left': {
					direction: 'rtl',
				},

				// 與 WordPress 衝突的 class
				'.tw-hidden': {
					display: 'none',
				},
				'.tw-columns-1': {
					columnCount: 1,
				},
				'.tw-columns-2': {
					columnCount: 2,
				},
				'.tw-fixed': {
					position: 'fixed',
				},
				'.tw-block': {
					display: 'block',
				},
				'.tw-inline': {
					display: 'inline',
				},
			}
			addUtilities(newUtilities, ['responsive', 'hover'])
		},
	],
	safelist: ['opacity-50'],
	blocklist: [
		'hidden',
		'columns-1',
		'columns-2',
		'fixed',
		'block',
		'inline',
		'rtl',
	],
	daisyui: {
		themes: [
			{
				power: {
					'color-scheme': 'light',
					primary: '#377cfb',
					'primary-content': '#223D30',
					secondary: '#66cc8a',
					'secondary-content': '#fff',
					accent: '#f68067',
					'accent-content': '#000',
					neutral: '#333c4d',
					'neutral-content': '#f9fafb',
					'base-100': 'oklch(100% 0 0)',
					'base-content': '#333c4d',
					'--animation-btn': '0',
					'--animation-input': '0',
					'--btn-focus-scale': '1',
				},
			},
			'light',
			'dark',
			'cupcake',
			'bumblebee',
			'emerald',
			'corporate',
			'synthwave',
			'retro',
			'cyberpunk',
			'valentine',
			'halloween',
			'garden',
			'forest',
			'aqua',
			'lofi',
			'pastel',
			'fantasy',
			'wireframe',
			'black',
			'luxury',
			'dracula',
			'cmyk',
			'autumn',
			'business',
			'night',
			'winter',
			'dim',
			'nord',
			'sunset',
		],
		prefix: 'pc-', // prefix for daisyUI classnames (components, modifiers and responsive class names. Not colors)
	},
}
