# VidStack Player - API Core Features

## Autoplay

An overview on requesting and tracking autoplay changes.

The `autoplay` prop can be set to request playback to begin automatically once media is ready.

### Autoplay Policy

Autoplay policy in web browsers establishes rules for when media can play without user interaction:

- **Muted Autoplay**: Most modern browsers allow muted autoplay by default, allowing videos to start playing with sound disabled. Users can manually enable audio if desired.
- **Unmuted Autoplay**: Playing audio with sound typically requires restrictions. Browsers usually demand user interaction — such as a click — before allowing sound-enabled playback, preventing unwanted noise.
- **User Gesture**: Media with sound may autoplay after a user performs an action like clicking a play button, once the user has engaged with the page.
- **Exceptions**: Certain websites receive exceptions based on user behavior patterns. Browsers may remember preferences if users frequently interact with media on specific sites.
- **Developer Best Practices**: When implementing autoplay, developers should understand browser policies. Using the autoplay attribute with JavaScript and user interaction provides better control over media playback.

> The best chance you have for autoplay working on a site where a user is visiting for the first time, or irregularly is by muting the media.

Encourage users to manually play or unmute content for optimal user experience. See Chrome's [autoplay policy documentation](https://developer.chrome.com/blog/autoplay/) for additional context.

### Styling

Media data attributes are available for styling based on autoplay state. Reference the `MediaPlayer` component documentation for applicable attributes.

### Events

Media events detect whether autoplay succeeds or fails. Use event listeners to handle autoplay outcomes accordingly.

---

## Fullscreen

An overview on requesting and tracking fullscreen mode changes.

Requesting fullscreen has similar requirements to autoplay policies. In general, it will not work programmatically until the user has interacted with the document first, or the request is in response to a user interaction like clicking or tapping a button.

### Requesting Fullscreen

Users can request fullscreen through three primary methods:

1. **FullscreenButton Component** — Built-in UI button
2. **Remote Control** — Media remote methods
3. **Player Instance Methods** — Direct method calls

#### Remote Control

The media remote dispatches request events:

- `media-enter-fullscreen-request`
- `media-exit-fullscreen-request`

#### Player Methods

Two primary methods are available on the player component instance:

- `enterFullscreen(target?)` — Enters fullscreen mode
- `exitFullscreen(target?)` — Exits fullscreen mode

#### Fullscreen Target Configuration

By default, `enterFullscreen()` attempts the Fullscreen API first (displaying custom UI), then falls back to the media provider. You can restrict fullscreen to the provider only by specifying the target parameter.

> Setting target to `provider` means custom UI won't display during fullscreen.

### State

Available properties on the media store:

| Property | Type | Description |
|----------|------|-------------|
| `canFullscreen` | `boolean` | Whether the native Fullscreen API or fullscreen on the current provider is available |
| `fullscreen` | `boolean` | Whether the player is currently in fullscreen mode |

### Styling

Media data attributes enable fullscreen state styling on the player component.

### Events

The following events detect fullscreen state changes or errors:

- Fullscreen change events
- Fullscreen error events

### Screen Orientation

Configure preferred fullscreen orientation via the `fullscreenOrientation` property. The Screen Orientation API locks orientation while fullscreen is active, then unlocks upon exit.

#### Disabling Orientation Lock

Set `fullscreenOrientation` to `none` to allow user-controlled screen rotation instead of automatic locking.

---

## Picture-in-Picture

An overview on requesting and tracking picture-in-picture mode changes.

The player leverages the standard Picture In Picture API on supported browsers and WebKit Presentation API on iOS Safari.

### Requesting PIP Changes

Users can request PIP mode changes through three mechanisms:

1. **PIPButton Component** — UI control for toggling PIP
2. **Remote Control** — Programmatic control via media remote
3. **Player Instance Methods** — Direct method calls on the player instance

#### Remote Control

The media remote dispatches PIP-related request events:

- `media-enter-pip-request`
- `media-exit-pip-request`

#### Methods

Two primary methods control PIP mode on the player component:

- `enterPictureInPicture()` — Request entering PIP mode
- `exitPictureInPicture()` — Request exiting PIP mode

### State

The media store exposes two PIP-related properties:

| Property | Type | Description |
|----------|------|-------------|
| `canPictureInPicture` | `boolean` | Whether the current browser or provider supports PIP functionality |
| `pictureInPicture` | `boolean` | Whether PIP mode is currently active |

### Styling

Media data attributes enable dynamic styling based on PIP state. These attributes correspond to the properties available on the `MediaPlayer` component.

### Events

The API provides events for detecting PIP mode transitions and error conditions, allowing developers to respond to state changes appropriately.

---

## Screen Orientation

An overview on requesting and tracking screen orientation changes.

The player uses the native [Screen Orientation API](https://developer.mozilla.org/en-US/docs/Web/API/Screen_Orientation_API) to lock and unlock the document's orientation.

### Browser Support

> Not all browsers support the Screen Orientation API methods equally. Notably, iOS Safari does not provide support for `lock` and `unlock` functionality.

Check browser compatibility at [caniuse.com](https://caniuse.com/screen-orientation).

### Requesting Orientation Changes

#### Via Remote Control

The media remote control provides methods for dispatching orientation-related events:

- `media-orientation-lock-request` — Initiates a screen lock request
- `media-orientation-unlock-request` — Initiates a screen unlock request

These events integrate with the player's state management system for updating orientation states.

#### Via Methods

The `ScreenOrientationController` exposes two primary methods:

- `lock(lockType)` — Locks the screen to a specified orientation type
- `unlock()` — Unlocks the screen to allow automatic rotation

Learn more about the [screen orientation lock types available](https://developer.mozilla.org/en-US/docs/Web/API/ScreenOrientation/lock#parameters) via MDN documentation.

### State

The media store provides these screen orientation properties:

| Property | Type | Description |
|----------|------|-------------|
| `canOrientScreen` | `boolean` | Whether the Screen Orientation API and required methods are supported |
| `orientation` | `string` | The current screen orientation value |

### Styling

Media data attributes are available for conditional styling based on orientation state. Reference the player media attributes documentation for implementation details.

### Events

The API dispatches events to notify components of orientation changes, enabling reactive UI updates based on current screen orientation state.

---

## Live

An overview on how live streams work and their API.

Live streams are supported by the [HLS Provider](/docs/player/api/providers/hls) which uses [hls.js](https://github.com/video-dev/hls.js/) in browsers that natively support HLS, and the [DASH Provider](/docs/player/api/providers/dash). The player will prefer using hls.js over the native engine when supported to enable a consistent and configurable experience across vendors.

### Stream Type

Refer to the [Stream Types section](/docs/player/core-concepts/loading#stream-type) for configuring the player for various playback and content types.

### Live DVR

Live DVR (Digital Video Recording) enables pausing, seeking backward, and playing live streams at a custom pace. The player attempts to infer DVR support but this is often inaccurate; instead, use the stream type property to specify DVR capabilities.

The `minLiveDVRWindow` property defines the minimum seekable duration in seconds before seeking is permitted (default: 60 seconds). Seeking operations are validated using this formula:

```
seekableWindow >= minLiveDVRWindow
```

### Live Edge

The live edge is a window from the starting edge of the live stream (`liveEdgeStart`) to the furthest seekable part of the media (`seekableEnd`).

#### How the Live Edge Start is Determined

**HLS Provider:**
- Uses the `liveSyncPosition` from hls.js
- Starting edge determined by `liveSyncDurationCount` (default multiple of `EXT-X-TARGETDURATION` is 3)

**Native iOS Safari:**
- Simply uses `seekableEnd` as the furthest seekable position

#### Tolerance

The `liveEdgeTolerance` property applies a safety buffer for buffering delays or accidental skips. Default value is 10 seconds, meaning playback can be 10 seconds behind live edge start and still be considered at the edge.

#### Live Edge Conditions

The player determines live edge status by checking:

1. If seeking is disabled (`canSeek` is false), the stream is always at the edge
2. The user hasn't intentionally seeked more than 2 seconds behind
3. Current playback time exceeds `liveEdgeStart` minus `liveEdgeTolerance`

#### Natural Fallback Behavior

If users fall behind through buffering or pausing, the player doesn't automatically catch them up. Users can manually seek to the live edge by scrubbing the time slider or pressing the live indicator.

#### Programmatic Access

Call `seekToLiveEdge()` on the player instance to programmatically seek to the current live edge.

### UI Adaptations

The following components automatically adapt to live streams:

- **`<Time>`**: Displays "LIVE" if the stream is non-seekable
- **`<TimeSlider>`**: Disabled for non-seekable streams; prevents interaction and pins the thumb to the right edge
- **`<SliderValue>`**: Shows negative offset from current live time when inside the time slider; displays "LIVE" for non-seekable streams

### State

Available live-related properties on the media store:

| Property | Type | Description |
|----------|------|-------------|
| `streamType` | `string` | Type of live stream (user-provided or inferred) |
| `live` | `boolean` | Whether current stream is live |
| `liveEdge` | `boolean` | Whether stream is within the live edge window (including tolerance) |
| `liveEdgeTolerance` | `number` | Seconds the current time can lag behind live edge start |
| `liveEdgeWindow` | `number` | Length of live edge window in seconds (defaults to 0 for non-live streams) |
| `minLiveDVRWindow` | `number` | Minimum seekable seconds for DVR operations (default: 30) |
| `canSeek` | `boolean` | Whether seeking is permitted |
| `seekableStart` | `number` | Earliest seekable time in seconds |
| `seekableEnd` | `number` | Latest seekable time in seconds |
| `seekableWindow` | `number` | Total seekable duration in seconds |
| `userBehindLiveEdge` | `boolean` | Whether user intentionally seeked 2+ seconds behind during live playback |

### Styling

Media data attributes are available for styling based on live state. See the media data attributes documentation.

### Events

Specific events are available for detecting live state changes.

---

## Keyboard

An overview on how to configure global keyboard shortcuts.

### Key Target

The `keyTarget` property determines where keyboard events are captured. Available options:

- **`document`**: Listens for key down events across the entire document. When multiple players exist on a page, only the recently interacted player receives input.
- **`player`**: Listens for key down events on the player element when it or its children have recent focus.

### Configuring Shortcuts

The `keyShortcuts` property extends the player's default keyboard shortcuts. You can define shortcuts in three ways:

- **Space-separated combination strings** — e.g., `p Control+Space`
- **Array of key values**
- **Custom callback functions**

This feature is based on the [`aria-keyshortcuts`](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Attributes/aria-keyshortcuts) attribute pattern.

> If `aria-keyshortcuts` is specified on a component then it will take precedence over the respective value set here.

### ARIA Key Shortcuts

Keyboard shortcuts can be defined on individual buttons through the `aria-keyshortcuts` attribute. When this attribute is set on a component, it overrides global configuration. If not explicitly set, the player automatically populates the attribute based on global settings, allowing screen readers to announce available shortcuts.

Refer to [MDN's `aria-keyshortcuts` documentation](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Attributes/aria-keyshortcuts) for best practices on shortcut selection.

### Disabling Keyboard

> We strongly recommend not disabling keyboard shortcuts for accessibility reasons, but if required you can disable them.

Disabling keyboard shortcuts does not affect standard ARIA keyboard controls for focused components.

---

## Audio Gain

An overview on requesting and tracking audio gain changes.

The player implements the [`GainNode`](https://developer.mozilla.org/en-US/docs/Web/API/GainNode) interface on `HTMLMediaElement` elements to apply audio gain. The gain value is a floating-point number representing amplification:

- `1` — default level (no change)
- `1.5` — increases volume by 50%
- `2` — doubles the volume

### Requesting Audio Gain Changes

Audio gain modifications can be initiated through three mechanisms:

1. **Audio Gain Slider Component** — Interactive UI control for gain adjustment
2. **Remote Control Interface** — Programmatic gain change requests
3. **Player Instance Methods** — Direct method calls on the player object

#### Remote Control

The media remote control dispatches `media-audio-gain-change-request` events to communicate gain adjustment requests to the player system.

#### Methods

The `setAudioGain(gain)` method on the player component instance enables direct audio gain modifications.

### State

The media store provides the following audio gain-related properties:

| Property | Type | Description |
|----------|------|-------------|
| `canSetAudioGain` | `boolean` | Whether audio gain is supported by the current browser and provider |
| `audioGain` | `number` | Current numeric gain value applied to audio output |

### Events

The system emits specific events for detecting and responding to audio gain modifications throughout the player lifecycle.

---

## Audio Tracks

An overview on using and configuring audio tracks.

> Audio tracks are currently supported by the HLS Provider and Dash Provider. They can not be added programmatically.

### Tracks List

The `audioTracks` property on the player returns an `AudioTrackList` object containing `AudioTrack` objects. This list is live — it dynamically updates as tracks are added or removed from the player.

You can monitor the list for changes by listening to list events to detect when new tracks are added or existing ones are removed.

#### AudioTrack Interface

The `AudioTrack` interface provides properties including:

- Track identification and metadata
- Language information
- Selection state

### Selecting Audio Tracks

The `selected` property allows you to set the current audio track. Once configured, the underlying provider will update the active audio track accordingly.

### List Events

The `AudioTrackList` object is an `EventTarget` that dispatches these events:

| Event | Description |
|-------|-------------|
| `add` | Fired when an audio track is added to the list |
| `remove` | Fired when an audio track is removed from the list |
| `change` | Fired when the selected audio track changes |

### State

These audio track-related properties are available on the media store:

| Property | Type | Description |
|----------|------|-------------|
| `audioTracks` | `AudioTrack[]` | Array containing the current list of AudioTrack objects |
| `audioTrack` | `AudioTrack \| null` | The current AudioTrack object, or null if none is available |

> React users should reference the `useAudioOptions` hook for building audio menus.

### Remote Control

Use the `changeAudioTrack` method on the media remote to dispatch `media-audio-track-change-request` request events for updating the current audio track.

### Media Events

Audio track-related events are available on the player for detecting track changes.

---

## Remote Playback

An overview on requesting and tracking remote playback changes (AirPlay and Google Cast).

The player supports playing on a remote device such as a TV when AirPlay or Google Cast are available. You can initiate remote playback using `AirPlayButton`, `GoogleCastButton`, remote control methods, or direct player instance methods.

### Requesting Remote Playback

The remote playback request follows an async, lazy-loaded workflow:

1. The Google Cast framework loads only when requested
2. Once loaded, the cast prompt displays to users
3. User selects a cast receiver device, triggering `GoogleCastProvider` lazy load and provider switch
4. The remote player synchronizes state (paused, time, muted, captions)
5. Provider change and setup events fire during this transition
6. When casting ends, `GoogleCastProvider` destroys and the previous provider restores
7. Original state reestablishes with additional provider events

> AirPlay does not require loading any framework as it's supported directly on the `<audio>` and `<video>` element via the Remote Playback API.

#### Remote Control

The media remote dispatches these request events:

- `media-airplay-request` — Request AirPlay playback
- `media-google-cast-request` — Request Google Cast playback

#### Methods

Two methods available on the player component instance:

- `requestAirPlay()` — Initiates AirPlay playback
- `requestGoogleCast()` — Initiates Google Cast playback

### State

The media store provides these remote playback properties:

| Property | Type | Description |
|----------|------|-------------|
| `canAirPlay` | `boolean` | AirPlay support availability |
| `canGoogleCast` | `boolean` | Google Cast support availability |
| `remotePlaybackState` | `string` | Current state: `connecting`, `connected`, or `disconnected` |
| `remotePlaybackType` | `string` | Type: `airplay` or `google-cast` |
| `remotePlaybackInfo` | `object` | Device name information for Google Cast |
| `isAirPlayConnected` | `boolean` | AirPlay connection status |
| `isGoogleCastConnected` | `boolean` | Google Cast connection status |

### Styling

Media data attributes support styling based on remote playback state through the player's media attributes system.

### Events

Events detect remote playback state changes and connection updates during the casting lifecycle.

---

## Text Tracks

An overview of using and configuring text tracks (captions/subtitles).

### Loading

Refer to the Loading Text Tracks guide for how to initialize tracks and supported formats/kinds.

### Tracks List

The read-only `textTracks` property on the player returns a `TextTrackList` object that contains `TextTrack` objects. These are custom implementations rather than the browser's native classes.

The returned list is live; as tracks are added to and removed from the player, the list's contents change dynamically. Monitor the list for changes to detect when new tracks are added or existing ones are removed by listening to list events.

#### List Events

The `TextTrackList` object is an `EventTarget` which dispatches the following events:

| Event | Description |
|-------|-------------|
| `add` | Fired when a text track has been added to the list |
| `remove` | Fired when a text track has been removed from the list |
| `mode-change` | Fired when the mode of any text track has changed |

### Managing Tracks

#### Add Tracks

Text tracks can be dynamically added and removed. You can also create tracks and add them programmatically.

#### Remove Tracks

Text tracks can be removed programmatically. All text tracks can be removed by calling `clear()`.

#### Track Mode

The `mode` property of a text track accepts the following values:

| Mode | Description |
|------|-------------|
| `showing` | Track will load, receive cue updates, and is visible on-screen |
| `hidden` | Track will load, receive cue updates, but is not visible on-screen |
| `disabled` | Track will not load and will not receive cue updates |

> Only one track per kind can have a mode of `showing`. Other tracks of the same kind that are specifically showing will have their mode set to `disabled` on change.

#### Track Events

The `TextTrack` object is an `EventTarget` which dispatches the following events:

| Event | Description |
|-------|-------------|
| `load-start` | Fired when the track begins loading |
| `load` | Fired when the track has finished loading and parsing |
| `error` | Fired when there is a critical error loading or parsing the track |
| `add-cue` | Fired when a new cue has been added |
| `remove-cue` | Fired when a cue has been removed |
| `cue-change` | Fired when the active cues have changed |
| `mode-change` | Fired when the mode has been changed |

### State

The following text track related properties are available on the media store:

| Property | Type | Description |
|----------|------|-------------|
| `textTracks` | `TextTrack[]` | An array containing the current list of TextTrack objects |
| `textTrack` | `TextTrack \| null` | The current captions/subtitles TextTrack object or null if none is showing |

> For React, check out the `useCaptionOptions` hook for building menus.

### Remote Control

The `changeTextTrack` method on the media remote can be used to dispatch `media-text-track-change-request` request events to update the current text track.

### Media Events

Text track related events are available on the player for detecting track changes and cue updates.

---

## Video Quality

An overview on requesting and tracking video quality changes.

### Loading

Video qualities can be created through multiple resolutions using adaptive streaming protocols like HLS or DASH, or by implementing multiple video files with source sizes. Refer to the Loading Video Qualities guide for detailed implementation approaches.

### Quality List

The player's read-only `qualities` property returns a `VideoQualityList` object containing `VideoQuality` objects, where each represents an available video rendition.

The list is live — contents update dynamically as qualities are added or removed. Monitor changes by listening to list events.

### Selecting Quality

Set the current video quality using the `selected` property:

```js
// Select a specific quality
const quality = player.qualities[0];
quality.selected = true;
```

**Important considerations:**

- Check `qualities.readonly` before setting — read-only lists ignore selection changes
- Manually setting quality disables automatic selection

### Switch Configuration

The `switch` property on `VideoQualityList` supports three modes:

| Mode | Description |
|------|-------------|
| `current` (default) | Immediately switch quality, aborting current fragment requests and flushing buffers |
| `next` | Queue quality switch for the next fragment |
| `load` | Apply quality level only to subsequently loaded fragments |

### Auto Select

Enable automatic quality selection via the `autoSelect()` method:

```js
// Re-enable automatic quality selection
player.qualities.autoSelect();
```

Manually selecting qualities disables auto mode; call `autoSelect()` to re-enable it.

### List Events

`VideoQualityList` dispatches these events:

| Event | Description |
|-------|-------------|
| `add` | Quality added to list |
| `remove` | Quality removed from list |
| `change` | Selected quality changed |
| `auto-change` | Auto-selection mode toggled |
| `readonly-change` | Read-only mode changed |

### State

Access these quality properties from the media store:

| Property | Type | Description |
|----------|------|-------------|
| `qualities` | `VideoQuality[]` | Array of available video quality objects |
| `quality` | `VideoQuality \| null` | Current video quality or null |
| `autoQuality` | `boolean` | Whether automatic quality selection is active |
| `canSetQuality` | `boolean` | Whether manual quality selection is available |

> React users: Use the `useVideoQualityOptions` hook for building quality selection menus.

### Remote Control

Update video quality via the `changeQuality` method on the media remote, which dispatches `media-quality-change-request` events.

### Media Events

The player broadcasts video quality-related events available through the media events system.
