import Player, { TPlayerProps } from '@/App2/Player'
import root from 'react-shadow'

function App2(dataset: TPlayerProps) {
	return (
		<root.div>
			<Player {...dataset} />
		</root.div>
	)
}

export default App2
