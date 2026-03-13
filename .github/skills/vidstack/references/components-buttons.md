# VidStack Player - Button Components

## Toggle Button

A generic two-state button component that displays "on" and "off" states. It functions as a button that can be either unpressed (off) or pressed (on).

### Usage

```tsx
import { ToggleButton, type ToggleButtonProps } from "@vidstack/react";

<ToggleButton aria-label="...">
  <OnIcon />
  <OffIcon />
</ToggleButton>
```

### Props

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |
| `children` | `ReactNode` | `null` |
| `defaultPressed` | `boolean` | `false` |
| `disabled` | `boolean` | `false` |

The component also accepts standard `HTMLAttributes<HTMLButtonElement>` and supports `Ref<HTMLButtonElement>` for ref forwarding.

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-pressed` | Indicates whether the toggle is in an "on" state (pressed) |
| `aria-pressed` | ARIA attribute reflecting pressed state as `"true"` or `"false"` |
| `data-focus` | Applied when button has keyboard focus |
| `data-hocus` | Applied when button has keyboard focus or is hovered |

### Styling Example

```css
/* Target pressed state */
.component[data-pressed] {}

/* Target focus state */
.component[data-focus] {}
```

---

## Play Button

A control component designed to toggle the playback state (play/pause) of media content when activated by the user.

### Usage

```tsx
import { PlayButton } from "@vidstack/react";

const isPaused = useMediaState('paused');

<PlayButton>
  {isPaused ? <PlayIcon /> : <PauseIcon />}
</PlayButton>
```

### Props

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |
| `children` | `ReactNode` | `null` |
| `disabled` | `boolean` | `undefined` |

The component extends standard `HTMLAttributes<HTMLButtonElement>` and accepts a `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaPlayRequest` | Triggered when user initiates playback |
| `onMediaPauseRequest` | Triggered when user requests pause |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-paused` | Indicates whether playback is currently stopped |
| `data-ended` | Indicates whether playback has concluded |

### Styling Example

```css
.component[data-paused] { /* styles for paused state */ }
```

---

## Mute Button

A button for toggling the muted state of the player. Allows users to control audio muting with a single click interaction.

### Usage

```tsx
import { MuteButton, type MuteButtonProps } from "@vidstack/react";

const volume = useMediaState('volume'),
  isMuted = useMediaState('muted');

<MuteButton>
  {isMuted || volume == 0 ? (
    <MuteIcon />
  ) : volume < 0.5 ? (
    <VolumeLowIcon />
  ) : (
    <VolumeHighIcon />
  )}
</MuteButton>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child component |
| `children` | `ReactNode` | `null` | Button content/children |
| `disabled` | `boolean` | `undefined` | Disable button interaction |

The component extends `HTMLAttributes<HTMLButtonElement>` and accepts `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaMuteRequest` | Triggered when muting is requested |
| `onMediaUnmuteRequest` | Triggered when unmuting is requested |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-muted` | Indicates whether volume is muted (0) |
| `data-state` | Reflects current volume level (low/high/muted) |

---

## Caption Button

A control component used to toggle the visibility of captions/subtitles during video playback. When activated, it toggles the current text track on or off.

### Usage

```tsx
import { CaptionButton, type CaptionButtonProps } from "@vidstack/react";

const track = useMediaState('textTrack'),
  isOn = track && isTrackCaptionKind(track);

<CaptionButton>
  {isOn ? <OnIcon /> : <OffIcon />}
</CaptionButton>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Enable child element rendering mode |
| `children` | `ReactNode` | `null` | Child content to render |
| `disabled` | `boolean` | `undefined` | Disable button interaction |

The component extends `HTMLAttributes<HTMLButtonElement>` and accepts `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaTextTrackChangeRequest` | Triggered when caption track change is requested |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-supported` | Indicates whether captions/subtitles are available |
| `data-active` | Indicates whether closed captions or subtitles are currently enabled |

---

## PIP Button

A button component that enables users to toggle picture-in-picture mode in the player.

### Usage

```tsx
import { PIPButton, type PIPButtonProps } from "@vidstack/react";

const isActive = useMediaState('pictureInPicture');

<PIPButton>
  {!isActive ? <EnterIcon /> : <ExitIcon />}
</PIPButton>
```

### Props

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |
| `children` | `ReactNode` | `null` |
| `disabled` | `boolean` | `undefined` |

The component extends `HTMLAttributes<HTMLButtonElement>` and accepts a `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaEnterPipRequest` | Fired when entering picture-in-picture mode is requested |
| `onMediaExitPipRequest` | Fired when exiting picture-in-picture mode is requested |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-active` | Indicates whether picture-in-picture mode is currently active |
| `data-supported` | Indicates whether picture-in-picture mode is available on the current device/browser |

### Styling Example

```css
.component[data-active] { /* PIP active styles */ }
.component[data-supported] { /* PIP supported styles */ }
```

---

## Fullscreen Button

A button component that enables users to toggle fullscreen mode for the video player.

### Usage

```tsx
import { FullscreenButton, type FullscreenButtonProps } from "@vidstack/react";

const isActive = useMediaState('fullscreen');

<FullscreenButton>
  {!isActive ? <EnterIcon /> : <ExitIcon />}
</FullscreenButton>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Button content/children |
| `disabled` | `boolean` | `undefined` | Disable the button |
| `target` | `MediaFullscreenRequestTarget` | `'prefer-media'` | Target for fullscreen request |

The component extends `HTMLAttributes<HTMLButtonElement>` and accepts `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaEnterFullscreenRequest` | Fired when entering fullscreen is requested |
| `onMediaExitFullscreenRequest` | Fired when exiting fullscreen is requested |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-active` | Indicates whether fullscreen mode is currently active |
| `data-supported` | Indicates whether fullscreen mode is supported by the browser/device |

### Styling Example

```css
.component[data-active] { /* fullscreen active styles */ }
.component[data-supported] { /* fullscreen supported styles */ }
```

---

## Live Button

Displays a live indicator and enables seeking to the live edge when pressed. This component displays the current live status of the stream, including whether it's live, at the live edge, or not live.

The component functions as an interactive button during active live streams, allowing viewers to jump to the current broadcast point. When the stream is not live, the component receives `aria-hidden="true"` for accessibility purposes.

### Usage

```tsx
import { LiveButton } from "@vidstack/react";

<LiveButton>
  <LiveIcon />
</LiveButton>
```

### Props

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |
| `children` | `ReactNode` | `null` |
| `disabled` | `boolean` | `false` |

The component extends `HTMLAttributes<HTMLButtonElement>` and accepts `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaLiveEdgeRequest` | Fired when seeking to the live edge is requested |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-edge` | Indicates playback is at the live edge |
| `data-hidden` | Shows when current media is not live |
| `data-focus` | Keyboard focus state |
| `data-hocus` | Keyboard focus or hover state |

---

## Seek Button

A component designed to advance or rewind media playback by a specified duration.

### Usage

```tsx
import { SeekButton, type SeekButtonProps } from "@vidstack/react";

<SeekButton seconds={-10}>
  <SeekBackwardIcon />
</SeekButton>

<SeekButton seconds={10}>
  <SeekForwardIcon />
</SeekButton>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Component content |
| `disabled` | `boolean` | `false` | Disable the button |
| `seconds` | `number` | `30` | Duration to seek in seconds (negative for backward) |

The component extends `HTMLAttributes<HTMLButtonElement>` and accepts `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaSeekRequest` | Triggered when a seek operation is requested |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-seeking` | Indicates an active seeking operation |
| `data-supported` | Shows whether seeking is allowed |
| `data-focus` | Applied during keyboard focus |
| `data-hocus` | Applied when keyboard-focused or hovered |

---

## AirPlay Button

A button component used to request remote playback via Apple AirPlay.

### Usage

```tsx
import { AirPlayButton } from "@vidstack/react";

<AirPlayButton>
  <AirPlayIcon />
</AirPlayButton>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Button content/children |
| `disabled` | `boolean` | `undefined` | Disable button interaction |

The component extends `HTMLAttributes<HTMLButtonElement>` and accepts `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaAirPlayRequest` | Triggered when an AirPlay session is requested |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-active` | Indicates whether an AirPlay connection is currently established |
| `data-supported` | Indicates whether AirPlay is available on the current device |

### Styling Example

```css
.component[data-supported] { /* AirPlay supported styles */ }
.component[data-active] { /* AirPlay active styles */ }
```

---

## Google Cast Button

A button component used to initiate remote playback via Google Cast technology. It integrates with the Vidstack player's Google Cast Provider.

### Usage

```tsx
import { GoogleCastButton, type GoogleCastButtonProps } from "@vidstack/react";

<GoogleCastButton>
  <ChromecastIcon />
</GoogleCastButton>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Determines if child components replace the button element |
| `children` | `ReactNode` | `null` | Content rendered inside the button |
| `disabled` | `boolean` | `undefined` | Disables button interaction |

The component extends `HTMLAttributes<HTMLButtonElement>` and accepts `Ref<HTMLButtonElement>`.

### Callbacks

| Callback | Description |
|----------|-------------|
| `onMediaGoogleCastRequest` | Triggered when a Google Cast session is requested |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-active` | Indicates whether a Google Cast connection is currently established |
| `data-supported` | Shows whether Google Cast capability is available on the device |
| `data-state` | Reflects the present connection status |

### Styling Example

```css
.component[data-supported] { /* cast supported styles */ }
.component[data-active] { /* cast active styles */ }
```

For comprehensive information about Google Cast integration, consult the [Google Cast Provider documentation](https://vidstack.io/docs/player/api/providers/google-cast).

---

## Tooltip

A contextual text bubble that displays descriptions for elements on interaction. It appears on pointer hover or keyboard focus.

### Component Structure

```tsx
<Tooltip.Root>
  <Tooltip.Trigger></Tooltip.Trigger>
  <Tooltip.Content></Tooltip.Content>
</Tooltip.Root>
```

### Root Component

Container for tooltip trigger and content elements.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Merges component with child element |
| `children` | `ReactNode` | `null` | Child elements |

### Trigger Component

Wraps the element that activates the tooltip on hover or keyboard focus. The tooltip content positions relative to this element.

| Prop | Type | Default |
|------|------|---------|
| `asChild` | `boolean` | `false` |
| `children` | `ReactNode` | `null` |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Indicates tooltip visibility state |
| `data-hocus` | Shows keyboard focus or hover status |

Extends `HTMLAttributes<HTMLButtonElement>` with `Ref<HTMLButtonElement>`.

### Content Component

Contains the visible tooltip text displayed during trigger interaction.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Tooltip text content |
| `alignOffset` | `number` | `0` | Horizontal alignment adjustment |
| `offset` | `number` | `0` | Vertical spacing from trigger |
| `placement` | `TooltipPlacement` | `'top center'` | Position relative to trigger |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Tooltip visibility state |
| `data-placement` | Current placement setting |
| `data-hocus` | Keyboard/hover status |

Extends `HTMLAttributes<HTMLElement>` with `Ref<HTMLElement>`.

### Usage with Buttons

```tsx
import { Tooltip } from "@vidstack/react";

<Tooltip.Root>
  <Tooltip.Trigger asChild>
    <PlayButton />
  </Tooltip.Trigger>
  <Tooltip.Content placement="top center">
    Play
  </Tooltip.Content>
</Tooltip.Root>
```
