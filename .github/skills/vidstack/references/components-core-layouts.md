# VidStack Player - Core & Layout Components

## Player Component

### Overview

The `MediaPlayer` component serves as the foundational element in Vidstack, responsible for managing media state, dispatching events, handling requests, and exposing playback information through HTML attributes and CSS properties.

### Core Structure

```jsx
import { MediaPlayer, type MediaPlayerProps } from "@vidstack/react";

<MediaPlayer src="...">
  <MediaProvider />
</MediaPlayer>
```

### Props Reference

#### Media Source & Content

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `src` | `PlayerSrc` | - | Media resource URL |
| `poster` | `string` | - | Display image before playback |
| `title` | `string` | - | Media title for display |
| `artist` | `string` | - | Audio track artist name |
| `artwork` | `MediaImage[]` | - | Album artwork images array |

#### Playback Control

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `autoPlay` / `autoplay` | `boolean` | `false` | Begin playback automatically |
| `paused` | `boolean` | `true` | Control pause state |
| `muted` | `boolean` | `false` | Mute audio track |
| `volume` | `number` | `1` | Audio level 0-1 |
| `playbackRate` | `number` | `1` | Playback speed multiplier |
| `loop` | `boolean` | `false` | Replay on completion |
| `currentTime` | `number` | `0` | Seek position in seconds |

#### Clipping & Duration

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `clipStartTime` | `number` | `0` | Begin playback at position |
| `clipEndTime` | `number` | `0` | End playback at position |
| `duration` | `number` | `-1` | Total media length |

#### Display & Layout

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `controls` | `boolean` | `false` | Show default control UI |
| `controlsDelay` | `number` | `2000` | Auto-hide delay in ms |
| `hideControlsOnMouseLeave` | `boolean` | - | Auto-hide on mouse exit |
| `aspectRatio` | `string` | - | Container dimensions ratio |
| `playsInline` / `playsinline` | `boolean` | - | Inline playback on iOS |

#### Loading Strategy

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `load` | `'idle' \| 'visible' \| 'play'` | `'visible'` | When to load media |
| `posterLoad` | `string` | `'visible'` | Poster image loading strategy |
| `preload` | `'none' \| 'metadata' \| 'auto'` | `'metadata'` | HTML preload attribute |

#### Stream Configuration

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `streamType` | `MediaStreamType` | - | Media stream type specification |
| `viewType` | `MediaViewType` | - | Display format: audio or video |
| `preferNativeHLS` | `boolean` | `false` | Use native HLS support |

#### Cross-Origin & Security

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `crossOrigin` / `crossorigin` | `'anonymous' \| 'use-credentials' \| null` | - | CORS policy |

#### Live Stream & DVR

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `liveEdgeTolerance` | `number` | `10` | Live edge buffer threshold |
| `minLiveDVRWindow` | `number` | `60` | Minimum DVR window size |

#### Remote Playback

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `googleCast` | `GoogleCastOptions` | - | Google Cast configuration options |

#### Accessibility & Input

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `keyDisabled` | `boolean` | `false` | Disable keyboard shortcuts |
| `keyShortcuts` | `MediaKeyShortcuts` | - | Custom keyboard bindings |
| `keyTarget` | `string` | `'player'` | Keyboard event target |

#### Display Options

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `fullscreenOrientation` | `string` | `'landscape'` | Preferred fullscreen orientation |

#### Development

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `logLevel` | `LogLevel` | - | Logging verbosity |
| `storage` | `string \| null` | - | Persistent state storage key |

#### Structure

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as different element |
| `children` | `ReactNode` | - | Child components |

### State Properties

Accessible via `useStore(MediaPlayerInstance, ref)`:

#### Playback Status

| Property | Type | Description |
|----------|------|-------------|
| `playing` | `boolean` | Active playback |
| `paused` | `boolean` | Paused state |
| `ended` | `boolean` | Playback completed |
| `waiting` | `boolean` | Buffering/loading |
| `seeking` | `boolean` | User seeking |
| `started` | `boolean` | Playback initiated |

#### Time & Duration

| Property | Type | Description |
|----------|------|-------------|
| `currentTime` | `number` | Current position |
| `duration` | `number` | Total length |
| `buffered` | `TimeRange` | Loaded ranges |
| `played` | `TimeRange` | Played ranges |
| `seekable` | `TimeRange` | Seekable ranges |

#### Media Information

| Property | Type | Description |
|----------|------|-------------|
| `mediaType` | `'audio' \| 'video'` | Media type |
| `mediaWidth` | `number` | Intrinsic width |
| `mediaHeight` | `number` | Intrinsic height |
| `title` | `string` | Media title |
| `artist` | `string` | Artist name |
| `artwork` | `MediaImage[]` | Artwork images |

#### Audio & Captions

| Property | Type | Description |
|----------|------|-------------|
| `audioTrack` | `object` | Current audio track |
| `audioTracks` | `array` | Available audio tracks |
| `textTrack` | `object` | Active captions/subtitles |
| `textTracks` | `array` | Available text tracks |
| `hasCaptions` | `boolean` | Caption availability |

#### Volume & Gain

| Property | Type | Description |
|----------|------|-------------|
| `volume` | `number` | Audio level 0-1 |
| `muted` | `boolean` | Muted status |
| `audioGain` | `number \| null` | Audio gain adjustment |

#### Quality Control

| Property | Type | Description |
|----------|------|-------------|
| `quality` | `object` | Current quality object |
| `qualities` | `array` | Available quality options |
| `autoQuality` | `boolean` | Automatic quality selection |

#### Screen & Fullscreen

| Property | Type | Description |
|----------|------|-------------|
| `fullscreen` | `boolean` | Fullscreen active |
| `pictureInPicture` | `boolean` | PiP mode active |
| `orientation` | `'landscape' \| 'portrait'` | Screen orientation |

#### Capabilities

| Property | Type | Description |
|----------|------|-------------|
| `canPlay` | `boolean` | Playback available |
| `canPause` | `boolean` | Pause available |
| `canSeek` | `boolean \| undefined` | Seeking permitted |
| `canLoad` | `boolean` | Loading permitted |
| `canFullscreen` | `boolean` | Fullscreen available |
| `canPictureInPicture` | `boolean` | PiP supported |
| `canSetVolume` | `boolean` | Volume control support |
| `canSetPlaybackRate` | `boolean` | Rate control support |
| `canAirPlay` | `boolean` | AirPlay support |
| `canGoogleCast` | `boolean` | Google Cast support |

#### Live Stream State

| Property | Type | Description |
|----------|------|-------------|
| `live` | `boolean \| undefined` | Live stream |
| `liveEdge` | `boolean \| undefined` | At live edge |
| `isLiveDVR` | `boolean \| undefined` | DVR available |
| `liveDVRWindow` | `number \| undefined` | DVR length |

#### Remote Playback

| Property | Type | Description |
|----------|------|-------------|
| `isAirPlayConnected` | `boolean` | AirPlay connected |
| `isGoogleCastConnected` | `boolean` | Google Cast connected |
| `remotePlaybackType` | `'airplay' \| 'google-cast' \| 'none'` | Remote type |
| `remotePlaybackState` | `'connecting' \| 'connected' \| 'disconnected'` | Remote state |

#### Error & Loading

| Property | Type | Description |
|----------|------|-------------|
| `error` | `object \| null` | Error object |
| `autoPlayError` | `object \| null` | Autoplay failure info |

#### Controls & UI

| Property | Type | Description |
|----------|------|-------------|
| `controls` | `boolean` | Controls enabled |
| `controlsVisible` | `boolean` | Controls displayed |
| `nativeControls` | `boolean \| undefined` | Native HTML controls |

#### Input Detection

| Property | Type | Description |
|----------|------|-------------|
| `pointer` | `'coarse' \| 'fine'` | Device type (touch or mouse) |

#### Dimensions

| Property | Type | Description |
|----------|------|-------------|
| `width` | `number` | Player width |
| `height` | `number` | Player height |
| `iOSControls` | `boolean \| undefined` | iOS native controls visible |

### Event Callbacks

All callbacks follow the pattern `on[Event]`.

#### Playback Events
- `onPlay` - Playback started
- `onPause` - Playback paused
- `onPlaying` - Playback actively playing
- `onEnded` - Playback completed
- `onReplay` - Replay triggered
- `onTimeUpdate` - Time position update
- `onTimeChange` - Time changed
- `onDurationChange` - Duration determined

#### Loading Events
- `onLoadStart` - Loading started
- `onCanLoad` - Can begin loading
- `onCanPlay` - Ready for playback
- `onCanPlayThrough` - Can play without buffering
- `onLoadedMetadata` - Metadata loaded
- `onLoadedData` - Data loaded
- `onSuspend` - Loading suspended
- `onAbort` - Loading aborted

#### Seeking Events
- `onSeeking` - Seek operation started
- `onSeeked` - Seek operation completed

#### Media State Events
- `onVolumeChange` - Volume changed
- `onRateChange` - Playback rate changed
- `onFullscreenChange` - Fullscreen state changed
- `onFullscreenError` - Fullscreen error
- `onPictureInPictureChange` - PiP state changed
- `onPictureInPictureError` - PiP error

#### Track Management Events
- `onAudioTrackChange` - Audio track changed
- `onAudioTracksChange` - Audio tracks list changed
- `onTextTrackChange` - Text track changed
- `onTextTracksChange` - Text tracks list changed
- `onQualityChange` - Quality changed
- `onQualitiesChange` - Qualities list changed

#### Remote Playback Events
- `onRemotePlaybackChange` - Remote state updates
- `onMediaAirplayRequest` - AirPlay requested
- `onMediaGoogleCastRequest` - Google Cast requested

#### Player Setup & Lifecycle
- `onProviderSetup` - Provider initialized
- `onProviderChange` - Provider changed
- `onProviderLoaderChange` - Loader changed
- `onMediaPlayerConnect` - Player instance ready
- `onDestroy` - Cleanup event

#### Live Stream Events
- `onLiveChange` - Live status changed
- `onLiveEdgeChange` - Live edge changed

#### Autoplay Events
- `onAutoPlay` - Autoplay activated
- `onAutoPlayChange` - Autoplay state changed
- `onAutoPlayFail` - Autoplay failed

#### Request Events (Media Control)
- `onMediaPlayRequest` - Play requested
- `onMediaPauseRequest` - Pause requested
- `onMediaSeekRequest` - Seek requested
- `onMediaVolumeChangeRequest` - Volume change requested
- `onMediaQualityChangeRequest` - Quality change requested
- `onMediaRateChangeRequest` - Rate change requested
- `onMediaTextTrackChangeRequest` - Text track change requested
- `onMediaAudioTrackChangeRequest` - Audio track change requested
- `onMediaEnterFullscreenRequest` - Enter fullscreen requested
- `onMediaExitFullscreenRequest` - Exit fullscreen requested
- `onMediaEnterPipRequest` - Enter PiP requested
- `onMediaExitPipRequest` - Exit PiP requested

#### Miscellaneous Events
- `onError` - Playback error
- `onWaiting` - Playback waiting
- `onStalled` - Playback stalled
- `onProgress` - Download progress
- `onSourceChange` - Source changed
- `onSourcesChange` - Sources list changed
- `onControlsChange` - Controls state changed
- `onLoopChange` - Loop state changed
- `onOrientationChange` - Orientation changed
- `onPlaysInlineChange` - Plays inline changed
- `onStreamTypeChange` - Stream type changed
- `onViewTypeChange` - View type changed
- `onStarted` - First playback initiated

### Instance Methods & Properties

Accessible via `ref.current`:

#### Playback Control

| Method | Description |
|--------|-------------|
| `play()` | Start playback |
| `pause()` | Pause playback |
| `seekToLiveEdge()` | Jump to live stream edge |

#### Fullscreen & PiP

| Method | Description |
|--------|-------------|
| `enterFullscreen(target?)` | Request fullscreen |
| `exitFullscreen(target?)` | Exit fullscreen |
| `enterPictureInPicture()` | Enter PiP mode |
| `exitPictureInPicture()` | Exit PiP mode |

#### Remote Playback

| Method | Description |
|--------|-------------|
| `requestAirPlay()` | Initiate AirPlay |
| `requestGoogleCast()` | Initiate Google Cast |

#### Audio Management

| Method | Description |
|--------|-------------|
| `setAudioGain(value)` | Adjust audio gain |

#### Loading Control

| Method | Description |
|--------|-------------|
| `startLoading()` | Begin media loading |
| `startLoadingPoster()` | Load poster image |

#### State & Subscription

| Property/Method | Description |
|----------------|-------------|
| `state` | Current player state object |
| `subscribe(listener)` | Subscribe to state changes |

#### Collections

| Property | Description |
|----------|-------------|
| `audioTracks` | AudioTrackList instance |
| `textTracks` | TextTrackList instance |
| `qualities` | VideoQualityList instance |

#### Provider & Rendering

| Property | Description |
|----------|-------------|
| `provider` | Active media provider (`AnyMediaProvider`) |
| `textRenderers` | Text track renderers |

#### Remote Control

| Property | Description |
|----------|-------------|
| `remoteControl` | MediaRemoteControl instance |

#### Queue Management

| Property | Description |
|----------|-------------|
| `canPlayQueue` | RequestQueue instance |

#### Screen Orientation

| Property | Description |
|----------|-------------|
| `orientation` | ScreenOrientationController instance |

### Data Attributes

HTML attributes applied for styling and state indication:

#### Playback State
- `data-paused` - Playback paused
- `data-playing` - Playback active
- `data-ended` - Playback completed
- `data-started` - Playback initiated
- `data-waiting` - Buffering/loading

#### Media Type
- `data-media-type` - Current type (`audio`/`video`)
- `data-view-type` - Display format
- `data-stream-type` - Stream type specification

#### Seeking & Buffering
- `data-seeking` - User seeking
- `data-buffering` - Not ready for playback
- `data-can-seek` - Seeking allowed
- `data-can-play` - Ready for playback

#### Audio
- `data-muted` - Volume muted
- `data-volume` - Current volume level

#### Controls & UI
- `data-controls` - Controls visible
- `data-ios-controls` - iOS native controls visible
- `data-load` - Loading strategy in use

#### Display Modes
- `data-fullscreen` - Fullscreen active
- `data-pip` - Picture-in-Picture active
- `data-orientation` - Screen orientation (`landscape`/`portrait`)
- `data-playsinline` - Inline playback enabled

#### Live Streams
- `data-live` - Live stream content
- `data-live-edge` - At live edge
- `data-captions` - Captions available and visible

#### Feature Availability
- `data-can-fullscreen` - Fullscreen supported
- `data-can-pip` - PiP supported
- `data-can-airplay` - AirPlay available
- `data-can-google-cast` - Google Cast available
- `data-airplay` - AirPlay connected
- `data-google-cast` - Google Cast connected

#### Autoplay
- `data-autoplay` - Autoplay successful
- `data-autoplay-error` - Autoplay failed

#### Error States
- `data-error` - Playback error occurred

#### Input Detection
- `data-pointer` - Input type (`coarse`/`fine`)
- `data-focus` - Keyboard focus active
- `data-hocus` - Focused or hovered
- `data-preview` - Slider interaction preview mode

#### Remote Playback
- `data-remote-type` - Remote type (`airplay`/`google-cast`)
- `data-remote-state` - Remote state (`connecting`/`connected`/`disconnected`)

---

## Provider Component

### Overview

The Provider component serves as a render target for the current media provider. It gives you complete control of where the provider will be rendered in the DOM. For video players, it renders the HTML `<video>` element; for audio, it renders the `<audio>` element.

### Basic Usage

```jsx
import { MediaProvider } from "@vidstack/react";

<MediaPlayer src="...">
  <MediaProvider />
</MediaPlayer>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `loaders` | `object` | `undefined` | Custom media loaders |
| `iframeProps` | `IframeHTMLAttributes<HTMLIFrameElement>` | `undefined` | Props passed to iframe elements |
| `mediaProps` | `HTMLAttributes<HTMLMediaElement>` | `undefined` | Props passed to media elements |
| `children` | `ReactNode` | `null` | Child components |

Additionally accepts standard `HTMLAttributes`.

### State

The component exposes state accessible via `MediaProviderInstance`:

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `loader` | `object` | `null` | Current media loader |

Access state using:

```jsx
const ref = useRef<MediaProviderInstance>(null);
const { loader } = useStore(MediaProviderInstance, ref);
```

### Instance Methods

| Method/Property | Description |
|----------------|-------------|
| `load` | Loads the provider |
| `subscribe` | Subscribes to state changes |
| `state` | Accesses `MediaProviderState` |

---

## Default Layout

### Overview

The Default Layout is a production-ready UI that is displayed when the media view type is set to `audio` or `video`. It supports audio tracks, captions, live streams, and more.

### Usage

#### View Type Configuration

The layout automatically detects view type from the provider and media type. You can manually specify:

```jsx
<DefaultVideoLayout viewType="video" />
<DefaultAudioLayout viewType="audio" />
```

Stream type can be similarly configured for live stream support.

### Color Scheme

Accepts `light` or `dark` values. Default behavior respects the user's preferred color scheme via CSS media queries.

Implementation options:
- Set `colorScheme` prop directly on the layout component
- Apply `light` or `dark` class to parent elements

### Size Configuration

Layouts adapt to small containers. Control activation threshold:

```jsx
<DefaultVideoLayout
  smallLayoutWhen={({ width, height }) => width < 576 || height < 380}
/>
```

Disable small layouts by setting query to `false` or `'never'`.

### Customization Options

#### Icons

Replace default icons to match application styling via the `icons` prop using `DefaultLayoutIcons` type.

#### Thumbnails

Provide preview images for time slider and chapters menu. See loading thumbnails guide for implementation details.

#### Language/i18n

Accept custom translations via `translations` prop. Dynamically update to change language at runtime.

```jsx
<DefaultVideoLayout translations={customTranslations} />
```

### Slots

#### Audio Layout Slots

Insert/replace content using the `slots` prop in `DefaultAudioLayout`:

```jsx
<DefaultAudioLayout slots={{ playButton: <CustomPlay /> }} />
```

#### Video Layout Slots

Similar functionality for `DefaultVideoLayout` with size-specific content insertion:

```jsx
<DefaultVideoLayout slots={{ lg: { playButton: <Custom /> } }} />
```

#### Available Slot Positions

**Display:**
- `bufferingIndicator`, `captionButton`, `captions`, `title`, `chapterTitle`, `currentTime`, `endTime`

**Buttons:**
- `fullscreenButton`, `liveButton`, `livePlayButton`, `muteButton`, `pipButton`, `airPlayButton`, `googleCastButton`, `playButton`, `loadButton`, `seekBackwardButton`, `seekForwardButton`

**Sliders:**
- `timeSlider`, `volumeSlider`, `startDuration`

**Menus:**
- `chaptersMenu`, `settingsMenu`, `playbackMenu`, `accessibilityMenu`, `audioMenu`, `captionsMenu`

**Modifiers:**
- Prefix positions with `before` or `after` for insertion placement

### API Reference

#### DefaultAudioLayout

**Import:** `@vidstack/react/player/layouts/default`

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `children` | `ReactNode` | `null` | Child content |
| `icons` | `DefaultLayoutIcons` | `undefined` | Custom icon set |
| `colorScheme` | `string` | `undefined` | Color scheme (`light` or `dark`) |
| `download` | `FileDownloadInfo` | `undefined` | Download configuration |
| `showTooltipDelay` | `number` | `700` | Tooltip display delay (ms) |
| `showMenuDelay` | `number` | `0` | Menu display delay (ms) |
| `hideQualityBitrate` | `boolean` | `false` | Hide bitrate in quality menu |
| `smallLayoutWhen` | `function` | `width < 576 \|\| height < 380` | Small layout activation |
| `thumbnails` | `ThumbnailSrc` | `undefined` | Thumbnail source |
| `translations` | `Partial<DefaultLayoutTranslations>` | `undefined` | Custom translations |
| `menuContainer` | `string` | `document.body` | Menu container element |
| `menuGroup` | `string` | `undefined` | Menu group |
| `noAudioGain` | `boolean` | `undefined` | Disable audio gain |
| `audioGains` | `object` | `undefined` | Audio gain options |
| `noModal` | `boolean` | `undefined` | Disable modal menus |
| `noScrubGesture` | `boolean` | `undefined` | Disable scrub gesture |
| `sliderChaptersMinWidth` | `number` | `undefined` | Min width for chapter marks |
| `disableTimeSlider` | `boolean` | `undefined` | Disable time slider |
| `noGestures` | `boolean` | `undefined` | Disable gestures |
| `noKeyboardAnimations` | `boolean` | `undefined` | Disable keyboard animations |
| `playbackRates` | `object` | `undefined` | Playback rate options |
| `seekStep` | `number` | `undefined` | Seek step size |
| `slots` | `DefaultAudioLayoutSlots` | `undefined` | Slot content |
| `asChild` | `boolean` | `false` | Render as child element |

#### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-match` | Indicates if layout is active |
| `data-sm` | Small layout active |
| `data-lg` | Large layout active |
| `data-size` | Active layout size (`sm` or `lg`) |

#### DefaultVideoLayout

**Import:** `@vidstack/react/player/layouts/default`

Props are identical to `DefaultAudioLayout` with the same prop signatures and defaults.

Data attributes are also the same (`data-match`, `data-sm`, `data-lg`, `data-size`).

---

## Plyr Layout

### Overview

The Plyr Layout is a pre-built audio/video player layout with built-in support for audio, video, and live streams. The view type is automatically inferred from the provider and media type.

### Usage

The layout automatically detects whether to display as audio, video, or live stream based on the provider. Stream types can also be explicitly specified.

### Customization Options

#### Icons

Icons can be replaced to match application styling by providing custom icon components through the `icons` prop.

#### Thumbnails

Preview images display during time slider interaction and scrubbing. Thumbnails are configured via the `thumbnails` property with supported sources and timing data.

#### Language & Internationalization

The `translations` property accepts custom language translations and can be updated dynamically to change the player language at runtime.

#### CSS Variables

Complete styling customization is available through CSS variables controlling colors, spacing, sizing, and other visual properties. All variables have documented default values.

### Slots

The `slots` prop enables inserting or replacing content at specific positions within the layout.

#### Available Slot Positions

- `airPlayButton`
- `captionsButton`
- `currentTime`
- `download`
- `duration`
- `fastForwardButton`
- `fullscreenButton`
- `liveButton`
- `muteButton`
- `pipButton`
- `playButton`
- `playLargeButton`
- `poster`
- `restartButton`
- `rewindButton`
- `settings`
- `settingsButton`
- `timeSlider`
- `volumeSlider`
- `settingsMenu`

Slots support `before` and `after` prefixes for positioning content relative to components.

### API Reference

**Import:**

```typescript
import { PlyrLayout } from "@vidstack/react/player/layouts/plyr";
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `icons` | `PlyrLayoutIcons` | `undefined` | Custom icon set |
| `posterFrame` | `number` | `undefined` | Poster frame number |
| `clickToPlay` | `boolean` | `undefined` | Click to toggle playback |
| `clickToFullscreen` | `boolean` | `undefined` | Double-click for fullscreen |
| `controls` | `PlyrControl[]` | `undefined` | Controls configuration |
| `displayDuration` | `boolean` | `undefined` | Show duration display |
| `download` | `FileDownloadInfo` | `undefined` | Download configuration |
| `markers` | `PlyrMarker[]` | `undefined` | Timeline markers |
| `invertTime` | `boolean` | `undefined` | Invert time display |
| `thumbnails` | `ThumbnailSrc` | `undefined` | Thumbnail source |
| `toggleTime` | `boolean` | `undefined` | Allow time display toggle |
| `translations` | `Partial<PlyrLayoutTranslations>` | `undefined` | Custom translations |
| `seekTime` | `number` | `undefined` | Seek time step |
| `speed` | `object` | `undefined` | Speed options |
| `slots` | `PlyrLayoutSlots` | `undefined` | Slot content |
| `asChild` | `boolean` | `false` | Render as child element |

#### Ref

`Ref<HTMLElement>` for accessing the underlying DOM element.
