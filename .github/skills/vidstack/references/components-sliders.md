# VidStack Player - Slider Components

## Slider

A versatile, accessible input control for selecting numeric values within a specified range. It supports both mouse and touch interactions with full ARIA compliance and cross-browser compatibility.

### Component Structure

The Slider is composed of several sub-components that work together:

- **Root** - The main container managing slider state and interaction
- **Track** - Visual bar representing the selectable range
- **TrackFill** - Highlighted portion showing the selected value
- **Thumb** - Draggable handle for value adjustment
- **Preview** - Real-time display of current selection (optional)
- **Value** - Numeric representation of the slider value
- **Steps** - Visual markers indicating value increments

### Basic Usage

```tsx
import { Slider } from "@vidstack/react";

<Slider.Root min={0} max={100} step={1} value={50}>
  <Slider.Track>
    <Slider.TrackFill />
  </Slider.Track>
  <Slider.Thumb />
</Slider.Root>
```

### Root Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child component |
| `disabled` | `boolean` | `undefined` | Disable slider interaction |
| `hidden` | `boolean` | `undefined` | Hide slider |
| `keyStep` | `number` | `undefined` | Keyboard stepping increment |
| `max` | `number` | `100` | Maximum selectable value |
| `min` | `number` | `0` | Minimum selectable value |
| `orientation` | `SliderOrientation` | `undefined` | Horizontal or vertical layout |
| `shiftKeyMultiplier` | `number` | `undefined` | Multiplier for shift+key navigation |
| `step` | `number` | `undefined` | Value increment size |
| `value` | `number` | `0` | Current slider value |

### Root State Properties

| Property | Type | Description |
|----------|------|-------------|
| `active` | `boolean` | Whether slider is being interacted with |
| `dragging` | `boolean` | Whether thumb is currently dragged |
| `fillPercent` | `number` | Fill rate as percentage |
| `fillRate` | `number` | Numeric fill representation |
| `focused` | `boolean` | Keyboard focus state |
| `hidden` | `boolean` | Visibility state |
| `pointerPercent` | `number` | Pointer position as percentage |
| `pointerRate` | `number` | Pointer position value |
| `pointerValue` | `number` | Current pointer value |
| `pointing` | `boolean` | Device hovering over slider |
| `step` | `number` | Current step size |
| `value` | `number` | Current slider value |

### Root Callbacks

| Callback | Description |
|----------|-------------|
| `onDragStart` | Triggered when dragging begins |
| `onDragEnd` | Triggered when dragging ends |
| `onDragValueChange` | Value changes during drag |
| `onPointerValueChange` | Pointer moves over slider |
| `onValueChange` | Any value change event |

### CSS Variables

| Variable | Description |
|----------|-------------|
| `--slider-fill` | Fill rate as percentage |
| `--slider-pointer` | Pointer position as percentage |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-dragging` | Thumb being dragged |
| `data-pointing` | Device hovering |
| `data-active` | Any interaction occurring |
| `data-focus` | Keyboard focused |

### Sub-Components

#### Track

Visual bar representing the selectable range.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### TrackFill

Highlighted portion showing the selected value.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Thumb

Draggable handle for value adjustment.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Preview

Real-time display of current selection with optional offset positioning.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `noClamp` | `boolean` | `false` | Prevent value clamping |
| `offset` | `number` | `0` | Position offset |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Preview visibility state |

#### Value

Numeric representation of slider state.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `decimalPlaces` | `number` | `2` | Decimal precision |
| `format` | `string` | `null` | Custom formatting |
| `padHours` | `boolean` | `null` | Pad hour values |
| `padMinutes` | `boolean` | `null` | Pad minute values |
| `showHours` | `boolean` | `false` | Display hours |
| `showMs` | `boolean` | `false` | Show milliseconds |
| `type` | `string` | `'pointer'` | Value type to display |

#### Steps

Visual markers indicating value increments along the track.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `children` | `function` | `null` | Render function receiving step values |
| `asChild` | `boolean` | `false` | Render as child |

### Instance Methods

Access via ref:
- `state` - Current slider state object
- `subscribe` - Subscribe to state changes

---

## Audio Gain Slider

A range input for controlling the current audio gain. Provides a versatile and user-friendly audio boost control designed for seamless cross-browser and provider compatibility and accessibility with ARIA support.

### Basic Usage

```tsx
import { AudioGainSlider } from "@vidstack/react";

<AudioGainSlider.Root>
  <AudioGainSlider.Track>
    <AudioGainSlider.TrackFill />
  </AudioGainSlider.Track>
  <AudioGainSlider.Thumb />
</AudioGainSlider.Root>
```

### Root Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `disabled` | `boolean` | `undefined` | Disable interaction |
| `hidden` | `boolean` | `undefined` | Hide slider |
| `keyStep` | `number` | `25` | Keyboard increment step |
| `max` | `number` | `300` | Maximum gain value |
| `min` | `number` | `0` | Minimum gain value |
| `orientation` | `SliderOrientation` | `undefined` | Horizontal or vertical layout |
| `shiftKeyMultiplier` | `number` | `2` | Shift key step multiplier |
| `step` | `number` | `25` | Value increment size |

### Root State Properties

| Property | Type | Default |
|----------|------|---------|
| `active` | `boolean` | `undefined` |
| `dragging` | `boolean` | `false` |
| `fillPercent` | `number` | `undefined` |
| `fillRate` | `number` | `undefined` |
| `focused` | `boolean` | `false` |
| `hidden` | `boolean` | `false` |
| `max` | `number` | `100` |
| `min` | `number` | `0` |
| `pointerPercent` | `number` | `undefined` |
| `pointerRate` | `number` | `undefined` |
| `pointerValue` | `number` | `0` |
| `pointing` | `boolean` | `false` |
| `step` | `number` | `1` |
| `value` | `number` | `0` |

### Root Callbacks

| Callback | Description |
|----------|-------------|
| `onDragStart` | Triggered when dragging begins |
| `onDragEnd` | Triggered when dragging ends |
| `onDragValueChange` | Fires during drag interactions |
| `onMediaAudioGainChangeRequest` | Handles audio gain change requests |
| `onPointerValueChange` | Fires when pointer value changes |
| `onValueChange` | Fires when value changes |

### CSS Variables

| Variable | Description |
|----------|-------------|
| `--slider-fill` | Fill rate expressed as a percentage |
| `--slider-pointer` | Pointer rate expressed as a percentage |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-dragging` | Whether slider thumb is being dragged |
| `data-pointing` | Whether user's pointing device is over slider |
| `data-active` | Whether slider is being interacted with |
| `data-focus` | Whether slider is keyboard focused |
| `data-hocus` | Whether slider is keyboard focused or hovered |
| `data-supported` | Whether audio gain is supported |

### Sub-Components

#### Thumb

A purely visual element used to display a draggable handle to the user for adjusting the value.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Track

A visual element that serves as a horizontal or vertical bar, providing a visual reference for the range.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### TrackFill

A portion of the slider track that is visually filled to indicate the selected range or value.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Preview

Used to provide users with a real-time or interactive preview of the value or selection they are making.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `noClamp` | `boolean` | `false` | Prevent value clamping |
| `offset` | `number` | `0` | Position offset |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Preview visibility state |

#### Value

Displays the specific numeric representation of the current or pointer value.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `decimalPlaces` | `number` | `2` | Decimal precision |
| `format` | `string` | `null` | Custom formatting |
| `padHours` | `boolean` | `null` | Pad hour values |
| `padMinutes` | `boolean` | `null` | Pad minute values |
| `showHours` | `boolean` | `false` | Display hours |
| `showMs` | `boolean` | `false` | Show milliseconds |
| `type` | `string` | `'pointer'` | Value type to display |

#### Steps

Visual markers indicating value steps on the slider track.

| Prop | Type | Default |
|------|------|---------|
| `children` | `function` | `null` |
| `asChild` | `boolean` | `false` |

---

## Time Slider

A range input component for controlling video playback position. Provides versatile and user-friendly input time control designed for seamless cross-browser and provider compatibility and accessibility with ARIA support.

### Basic Usage

```tsx
import { TimeSlider } from "@vidstack/react";

<TimeSlider.Root>
  <TimeSlider.Track>
    <TimeSlider.TrackFill />
    <TimeSlider.Progress />
  </TimeSlider.Track>
  <TimeSlider.Thumb />
</TimeSlider.Root>
```

### Root Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Merge component with child element |
| `disabled` | `boolean` | `undefined` | Disable slider interaction |
| `hidden` | `boolean` | `undefined` | Hide the slider |
| `keyStep` | `number` | `5` | Keyboard increment step |
| `noSwipeGesture` | `boolean` | `false` | Disable swipe gestures |
| `orientation` | `SliderOrientation` | `undefined` | Horizontal or vertical layout |
| `pauseWhileDragging` | `boolean` | `false` | Pause playback during drag |
| `seekingRequestThrottle` | `number` | `100` | Seeking request throttle in ms |
| `shiftKeyMultiplier` | `number` | `2` | Shift key multiplier for steps |
| `step` | `number` | `0.1` | Value increment step |

### Root State Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `active` | `boolean` | `undefined` | Slider interaction status |
| `dragging` | `boolean` | `false` | Thumb drag status |
| `fillPercent` | `number` | `undefined` | Fill percentage |
| `focused` | `boolean` | `false` | Keyboard focus status |
| `max` | `number` | `100` | Maximum value |
| `min` | `number` | `0` | Minimum value |
| `pointerPercent` | `number` | `undefined` | Pointer position percentage |
| `pointing` | `boolean` | `false` | Pointer hover status |
| `value` | `number` | `0` | Current slider value |

### Root Callbacks

| Callback | Description |
|----------|-------------|
| `onDragStart` | Fired when dragging begins |
| `onDragEnd` | Fired when dragging ends |
| `onDragValueChange` | Called during drag with new value |
| `onMediaLiveEdgeRequest` | Requests jumping to live edge |
| `onMediaPauseRequest` | Requests media pause |
| `onMediaPlayRequest` | Requests media play |
| `onMediaSeekRequest` | Requests seek to time |
| `onMediaSeekingRequest` | Requests seeking state |
| `onPointerValueChange` | Called when pointer position changes |
| `onValueChange` | Called when value changes |

### CSS Variables

| Variable | Description |
|----------|-------------|
| `--slider-fill` | Fill rate as percentage |
| `--slider-pointer` | Pointer rate as percentage |
| `--slider-progress` | Buffered playback percentage |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-dragging` | Thumb being dragged |
| `data-pointing` | Pointer over slider |
| `data-active` | Slider interaction status |
| `data-focus` | Keyboard focus status |
| `data-hocus` | Keyboard focused or hovered |

### Sub-Components

#### Track

Contains the visual slider track element with optional fill and progress indicators.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### TrackFill

Represents the selected range portion, dynamically adjusting as the thumb moves to indicate user selection.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Progress

Displays a visual reference for the range of playback that has buffered/loaded.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Thumb

A draggable handle allowing users to adjust the playback position along the track.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Preview

Provides real-time feedback during slider interaction, displaying current position numerically or via thumbnail.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `noClamp` | `boolean` | `false` | Prevent value clamping |
| `offset` | `number` | `0` | Position offset |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Preview visibility state |

#### Value

Displays the numeric representation of current or pointer slider value.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `decimalPlaces` | `number` | `2` | Decimal precision |
| `format` | `string` | `null` | Custom format string |
| `padHours` | `boolean` | `null` | Pad hour values |
| `padMinutes` | `boolean` | `null` | Pad minute values |
| `showHours` | `boolean` | `false` | Display hours |
| `showMs` | `boolean` | `false` | Display milliseconds |
| `type` | `string` | `'pointer'` | Value type to display |

#### Chapters

Creates predefined sections based on active chapters text track.

| Prop | Type | Description |
|------|------|-------------|
| `onChapterFill` | `function` | Chapter fill callback |
| `onChapterProgress` | `function` | Chapter progress callback |

#### ChapterTitle

Displays active cue text based on slider and preview values.

#### Video

Loads low-resolution preview video synchronized with slider position.

| Prop | Type | Description |
|------|------|-------------|
| `src` | `string` | Video source URL |
| `crossOrigin` | `string` | CORS setting |

**Callbacks:**

| Callback | Description |
|----------|-------------|
| `onCanPlay` | Fired when video can start playing |
| `onError` | Fired on loading error |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-loading` | Video is loading |
| `data-error` | Loading error occurred |
| `data-hidden` | Video is hidden |

#### Steps

Visual markers indicating value steps on the track.

| Prop | Type | Default |
|------|------|---------|
| `children` | `function` | `null` |
| `asChild` | `boolean` | `false` |

---

## Volume Slider

A range input for controlling the volume of media. Provides seamless cross-browser and provider compatibility and accessibility with ARIA support. Supports both mouse and touch interactions.

### Basic Usage

```tsx
import { VolumeSlider } from "@vidstack/react";

<VolumeSlider.Root>
  <VolumeSlider.Track>
    <VolumeSlider.TrackFill />
  </VolumeSlider.Track>
  <VolumeSlider.Thumb />
</VolumeSlider.Root>
```

### With Preview

```tsx
<VolumeSlider.Root>
  <VolumeSlider.Preview>
    <VolumeSlider.Value />
  </VolumeSlider.Preview>
  <VolumeSlider.Track>
    <VolumeSlider.TrackFill />
  </VolumeSlider.Track>
  <VolumeSlider.Thumb />
</VolumeSlider.Root>
```

### Root Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `disabled` | `boolean` | `undefined` | Disable interaction |
| `hidden` | `boolean` | `undefined` | Hide slider |
| `keyStep` | `number` | `5` | Keyboard increment step |
| `orientation` | `SliderOrientation` | `undefined` | Horizontal or vertical layout |
| `shiftKeyMultiplier` | `number` | `2` | Shift key step multiplier |
| `step` | `number` | `undefined` | Value increment size |

### Root State Properties

| Property | Type | Default |
|----------|------|---------|
| `active` | `boolean` | `undefined` |
| `dragging` | `boolean` | `false` |
| `fillPercent` | `number` | `undefined` |
| `fillRate` | `number` | `undefined` |
| `focused` | `boolean` | `false` |
| `hidden` | `boolean` | `false` |
| `max` | `number` | `100` |
| `min` | `number` | `0` |
| `pointerPercent` | `number` | `undefined` |
| `pointerRate` | `number` | `undefined` |
| `pointerValue` | `number` | `0` |
| `pointing` | `boolean` | `false` |
| `step` | `number` | `1` |
| `value` | `number` | `0` |

### Root Callbacks

| Callback | Description |
|----------|-------------|
| `onDragStart` | Triggered when dragging begins |
| `onDragEnd` | Triggered when dragging ends |
| `onDragValueChange` | Value changes during drag |
| `onMediaVolumeChangeRequest` | Handles volume change requests |
| `onPointerValueChange` | Pointer position changes |
| `onValueChange` | Any value change event |

### CSS Variables

| Variable | Description |
|----------|-------------|
| `--slider-fill` | Fill rate as percentage |
| `--slider-pointer` | Pointer rate as percentage |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-dragging` | Indicates thumb drag state |
| `data-pointing` | User's pointing device presence |
| `data-active` | Active interaction state |
| `data-focus` | Keyboard focus state |
| `data-hocus` | Keyboard focus or hover state |
| `data-supported` | Volume control support |

### Sub-Components

#### Thumb

Visual draggable handle for value adjustment.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Track

Horizontal or vertical bar providing visual reference.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### TrackFill

Highlighted portion indicating selected range.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Preview

Real-time preview of current selection.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `noClamp` | `boolean` | `false` | Prevent value clamping |
| `offset` | `number` | `0` | Position offset |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Preview visibility state |

#### Value

Numeric representation of current value.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `decimalPlaces` | `number` | `2` | Decimal precision |
| `format` | `string` | `null` | Custom formatting |
| `padHours` | `boolean` | `null` | Pad hour values |
| `padMinutes` | `boolean` | `null` | Pad minute values |
| `showHours` | `boolean` | `false` | Display hours |
| `showMs` | `boolean` | `false` | Show milliseconds |
| `type` | `string` | `'pointer'` | Value type to display |

#### Steps

Visual markers for value steps.

| Prop | Type | Default |
|------|------|---------|
| `children` | `function` | `null` |
| `asChild` | `boolean` | `false` |

---

## Speed Slider

A range input for controlling the current playback rate. Provides an accessible, cross-browser control for adjusting playback speed with full ARIA support.

### Basic Usage

```tsx
import { SpeedSlider } from "@vidstack/react";

<SpeedSlider.Root>
  <SpeedSlider.Track>
    <SpeedSlider.TrackFill />
  </SpeedSlider.Track>
  <SpeedSlider.Thumb />
</SpeedSlider.Root>
```

### Root Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `disabled` | `boolean` | `undefined` | Disable interaction |
| `hidden` | `boolean` | `undefined` | Hide slider |
| `keyStep` | `number` | `0.25` | Keyboard increment step |
| `max` | `number` | `2` | Maximum speed value |
| `min` | `number` | `0` | Minimum speed value |
| `orientation` | `SliderOrientation` | `undefined` | Horizontal or vertical layout |
| `shiftKeyMultiplier` | `number` | `2` | Shift key step multiplier |
| `step` | `number` | `0.25` | Value increment size |

### Root State Properties

| Property | Type | Default |
|----------|------|---------|
| `active` | `boolean` | `undefined` |
| `dragging` | `boolean` | `false` |
| `fillPercent` | `number` | `undefined` |
| `fillRate` | `number` | `undefined` |
| `focused` | `boolean` | `false` |
| `hidden` | `boolean` | `false` |
| `max` | `number` | `100` |
| `min` | `number` | `0` |
| `pointerPercent` | `number` | `undefined` |
| `pointerRate` | `number` | `undefined` |
| `pointerValue` | `number` | `0` |
| `pointing` | `boolean` | `false` |
| `step` | `number` | `1` |
| `value` | `number` | `0` |

### Root Callbacks

| Callback | Description |
|----------|-------------|
| `onDragStart` | Triggered when dragging begins |
| `onDragEnd` | Triggered when dragging ends |
| `onDragValueChange` | Value changes during drag |
| `onMediaRateChangeRequest` | Handles playback rate change requests |
| `onPointerValueChange` | Pointer position changes |
| `onValueChange` | Any value change event |

### CSS Variables

| Variable | Description |
|----------|-------------|
| `--slider-fill` | The fill rate as a percentage |
| `--slider-pointer` | The pointer rate as a percentage |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-dragging` | Indicates thumb drag state |
| `data-pointing` | Indicates pointer presence over slider |
| `data-active` | Indicates active interaction |
| `data-focus` | Indicates keyboard focus state |
| `data-hocus` | Indicates keyboard focus or hover |
| `data-supported` | Indicates playback rate support |

### Sub-Components

#### Thumb

Displays a draggable visual handle.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Track

A horizontal or vertical bar providing a visual reference for value selection.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### TrackFill

The highlighted portion representing the selected range.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Preview

Provides interactive feedback during interaction.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `noClamp` | `boolean` | `false` | Prevent value clamping |
| `offset` | `number` | `0` | Position offset |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Preview visibility state |

#### Value

Displays the specific numeric representation of slider state.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `decimalPlaces` | `number` | `2` | Decimal precision |
| `format` | `string` | `null` | Custom formatting |
| `padHours` | `boolean` | `null` | Pad hour values |
| `padMinutes` | `boolean` | `null` | Pad minute values |
| `showHours` | `boolean` | `false` | Display hours |
| `showMs` | `boolean` | `false` | Show milliseconds |
| `type` | `string` | `'pointer'` | Value type to display |

#### Steps

Visual markers indicating value intervals on the track.

| Prop | Type | Default |
|------|------|---------|
| `children` | `function` | `null` |
| `asChild` | `boolean` | `false` |

---

## Quality Slider

A range input for controlling the current playback quality. Provides versatile and user-friendly input video quality control designed for seamless cross-browser and provider compatibility and accessibility with ARIA support.

### Basic Usage

```tsx
import { QualitySlider } from "@vidstack/react";

<QualitySlider.Root>
  <QualitySlider.Track>
    <QualitySlider.TrackFill />
  </QualitySlider.Track>
  <QualitySlider.Thumb />
</QualitySlider.Root>
```

### Root Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `disabled` | `boolean` | `undefined` | Disable interaction |
| `hidden` | `boolean` | `undefined` | Hide slider |
| `keyStep` | `number` | `1` | Keyboard increment step |
| `orientation` | `SliderOrientation` | `undefined` | Horizontal or vertical layout |
| `shiftKeyMultiplier` | `number` | `1` | Shift key step multiplier |
| `step` | `number` | `1` | Value increment size |

### Root State Properties

| Property | Type | Default |
|----------|------|---------|
| `active` | `boolean` | `undefined` |
| `dragging` | `boolean` | `false` |
| `fillPercent` | `number` | `undefined` |
| `fillRate` | `number` | `undefined` |
| `focused` | `boolean` | `false` |
| `hidden` | `boolean` | `false` |
| `max` | `number` | `100` |
| `min` | `number` | `0` |
| `pointerPercent` | `number` | `undefined` |
| `pointerRate` | `number` | `undefined` |
| `pointerValue` | `number` | `0` |
| `pointing` | `boolean` | `false` |
| `step` | `number` | `1` |
| `value` | `number` | `0` |

### Root Callbacks

| Callback | Description |
|----------|-------------|
| `onDragStart` | Triggered when dragging begins |
| `onDragEnd` | Triggered when dragging ends |
| `onDragValueChange` | Value changes during drag |
| `onMediaQualityChangeRequest` | Handles quality change requests |
| `onPointerValueChange` | Pointer position changes |
| `onValueChange` | Any value change event |

### CSS Variables

| Variable | Description |
|----------|-------------|
| `--slider-fill` | Fill rate expressed as percentage |
| `--slider-pointer` | Pointer rate expressed as percentage |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-dragging` | Thumb being dragged |
| `data-pointing` | Pointing device over slider |
| `data-active` | Slider being interacted with |
| `data-focus` | Keyboard focused |
| `data-hocus` | Keyboard focused or hovered |
| `data-supported` | Setting video quality supported |

### Sub-Components

#### Thumb

Draggable handle for quality value adjustment.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Track

Visual bar providing reference for selectable quality range.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### TrackFill

Highlighted portion showing selected quality range.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |

#### Preview

Displays real-time preview of quality selections.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `noClamp` | `boolean` | `false` | Prevent value clamping |
| `offset` | `number` | `0` | Position offset |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Preview visibility state |

#### Value

Shows numeric representation of current quality value.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child |
| `children` | `ReactNode` | `null` | Child content |
| `decimalPlaces` | `number` | `2` | Decimal precision |
| `format` | `string` | `null` | Custom formatting |
| `padHours` | `boolean` | `null` | Pad hour values |
| `padMinutes` | `boolean` | `null` | Pad minute values |
| `showHours` | `boolean` | `false` | Display hours |
| `showMs` | `boolean` | `false` | Show milliseconds |
| `type` | `string` | `'pointer'` | Value type to display |

#### Steps

Visual markers indicating quality step increments.

| Prop | Type | Default |
|------|------|---------|
| `children` | `function` | `null` |
| `asChild` | `boolean` | `false` |
