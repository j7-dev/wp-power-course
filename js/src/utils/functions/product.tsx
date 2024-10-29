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
