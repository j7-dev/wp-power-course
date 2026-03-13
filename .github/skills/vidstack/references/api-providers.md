# VidStack Player - API Providers

## Audio Provider

The audio provider facilitates playback of sound content through the native HTML5 `<audio>` element, allowing developers to embed audio files into web applications.

### Sources

#### Single Source

You can specify a single audio file directly as a string URL:

```
src="https://example.com/audio.mp3"
```

#### Multiple Sources

The provider supports multiple source specifications, allowing fallback options across different formats and browsers.

#### Source Objects

Beyond simple URL strings, the `src` property accepts several object types:

- `MediaStream`
- `MediaSource`
- `Blob`
- `File`

When using these object types, you may optionally specify the source type as `audio/object`.

### Source Types

#### Required File Extensions and Type Hints

Valid audio file extensions include: **m4a, m4b, mp4a, mpga, mp2, mp2a, mp3, m2a, m3a, wav, weba, aac, oga, spx**

If your source URL lacks a file extension, you must provide a type hint from: **audio/mpeg, audio/ogg, audio/3gp, audio/mp4, audio/webm, audio/flac, audio/object**

#### Valid Examples

- `src="https://example.com/audio.mp3"`
- `src = { src: "https://example.com/audio", type: "audio/mp3" }`

### Element Reference

Developers can obtain a reference to the underlying `HTMLAudioElement` through the player component.

### Event Target

The `HTMLAudioElement` can be accessed and referenced on all media events emitted by the player.

---

## Video Provider

The Video Provider enables playback of video files through the native HTML5 `<video>` element, allowing developers to embed video content directly into web applications.

### Sources

#### Single Source

The provider supports a straightforward approach where you specify a video URL as the source.

#### Source Sizes

The provider accommodates responsive video delivery through source size specifications.

#### Source Types

Multiple source formats can be provided to ensure cross-browser compatibility.

#### Source Object

The `src` property accepts several object types:

- `MediaStream`
- `MediaSource`
- `Blob`
- `File`

You may optionally designate these as video objects by setting the source `type` to `video/object`.

### Source Types

Valid video file extensions include: **mp4, ogg, ogv, webm, mov, m4v**.

When a source URL lacks a file extension, provide one of these MIME type hints: **video/mp4, video/webm, video/3gp, video/ogg, video/avi, video/mpeg**.

#### Valid Source Examples

- `src="https://example.com/video.mp4"`
- `src = { src: "https://example.com/video", type: "video/mp4" }`

### Element Access

Developers can obtain a reference to the underlying `HTMLVideoElement` for direct manipulation or inspection.

### Event Target

The `HTMLVideoElement` is accessible on all media events, allowing event handlers to interact with the native video element directly.

---

## HLS Provider

The HLS provider enables streaming media using the HTTP Live Streaming protocol. It leverages the native `<video>` element and prefers using `hls.js` over the native engine when supported to ensure consistent experiences across browsers. The provider works wherever Media Source Extensions (MSE) or Managed Media Source (MMS) is available.

### Installation

Install the `hls.js` library if using the provider locally:

```bash
npm install hls.js
```

### Setting Sources

HLS source URLs should include the `.m3u8` file extension. If the extension is absent, provide a type hint such as `application/vnd.apple.mpegurl`, `audio/mpegurl`, or `application/x-mpegurl`.

Valid source examples:

- `src="https://example.com/hls.m3u8"`
- `src = { src: "https://example.com/hls", type: "application/x-mpegurl" }`

### Loading hls.js

By default, the library loads from JSDelivr (default bundle in development, minified in production). You can redirect to any URL that re-exports `hls.js@^1.0`.

#### Static/Dynamic Import

Statically or dynamically import hls.js and set the `library` property on the provider.

#### Load Events

The provider fires events during library loading:

- `onHlsLibLoadStart`
- `onHlsLibLoaded`
- `onHlsLibLoadError`

### Configuration

Configure hls.js via the `config` property. Refer to the [hls.js fine tuning guide](https://github.com/video-dev/hls.js/blob/master/docs/API.md#fine-tuning) for available options.

#### Custom Headers

Set custom request headers using `xhrSetup`:

```javascript
config={{
  xhrSetup: (xhr, url) => {
    xhr.setRequestHeader('Authorization', 'Bearer token');
  }
}}
```

### Instance Access

Access the hls.js instance via `onHlsInstance` callback to interact directly with the library.

### Events

All hls.js events are available as callbacks on the player component, prefixed with `onHls` (e.g., `onHlsError`, `onHlsLevelSwitched`). Event types follow the pattern `HLS[EventName]Event`.

Notable events include:

- Audio/subtitle track events
- Buffer and manifest events
- Level/quality switching events
- Fragment loading events
- Error handling

### Element Reference

Access the underlying `HTMLVideoElement` through player methods or media event references.

---

## DASH Provider

The DASH provider enables streaming media using Dynamic Adaptive Streaming over HTTP protocol. It leverages the native `<video>` element with [`dash.js`](https://github.com/Dash-Industry-Forum/dash.js) to support DASH streaming, which is not natively supported by browsers. The provider works where Media Source Extensions (MSE) or Managed Media Source (MMS) is available.

### Installation

Install the required `dash.js` library for local usage:

```bash
npm install dash.js
```

### Source Configuration

#### Setting DASH Sources

Configure a DASH source on the player with URLs containing the `.mpd` file extension.

#### Source Types

Valid DASH source formats include:

- `src="https://example.com/dash.mpd"`
- `src = { src: "https://example.com/dash", type: "application/dash+xml" }`

If your URL lacks a file extension, provide the `"application/dash+xml"` type hint.

### Loading dash.js

#### Default Behavior

The provider automatically loads `dashjs` from JSDelivr, using the default bundle in development and minified version in production.

#### Static/Dynamic Import

Load `dashjs` locally and pass it to the provider:

```javascript
import dashjs from 'dash.js';
// Set library property on provider
```

#### Load Events

The provider fires these events while loading the library:

- `onDashLibLoadStart`
- `onDashLibLoaded`
- `onDashLibLoadError`

### Configuration

Configure `dashjs` via the `config` property on the provider component.

### Accessing Instances

Obtain references to the `dashjs` instance through the player component's event handlers and methods.

### Events

All dashjs events can be listened to via the player component using `onDash[EventName]` callbacks. Notable events include:

- **Playback**: `onDashPlaybackPlaying`, `onDashPlaybackPaused`, `onDashPlaybackEnded`
- **Quality**: `onDashQualityChangeRequested`, `onDashQualityChangeRendered`
- **Buffering**: `onDashBufferLevelUpdated`, `onDashBufferEmpty`
- **Streaming**: `onDashStreamInitialized`, `onDashStreamUpdated`
- **Manifest**: `onDashManifestLoaded`, `onDashManifestLoadingStarted`

Complete event list includes 80+ DASH-specific callbacks for granular control.

### Element Access

Reference the underlying `HTMLVideoElement` through the player component for direct DOM manipulation when needed.

---

## YouTube Provider

The YouTube provider allows video embedding from YouTube through the IFrame API. It offers several advantages:

- Preconnections for 224x faster rendering (using the same technique as `lite-youtube-embed`)
- Automatic WebP poster discovery with JPG fallback
- Complete loading control via load strategies (lazy-loaded by default)
- Autoplay failure detection
- GDPR-compliant cookie handling
- Hidden recommendation popups with custom controls
- Full access to Vidstack Player capabilities

### Supported Source URLs

The provider recognizes these URL formats:

- `youtube/_cMxraX_5RE`
- `https://www.youtube.com/watch?v=_cMxraX_5RE`
- `https://www.youtube-nocookie.com/watch?v=_cMxraX_5RE`
- `https://www.youtube.com/embed/_cMxraX_5RE`
- `https://youtu.be/_cMxraX_5RE`

Replace `_cMxraX_5RE` with your actual video ID, found in YouTube video URLs.

### Configuration

You can configure specific embed options on the provider. For complete configuration examples and all available props, methods, and events, consult the full [Player component documentation](https://vidstack.io/docs/wc/player/components/core/player).

---

## Vimeo Provider

The Vimeo provider enables playback of videos hosted on Vimeo using the IFrame API. It integrates seamlessly with Vidstack Player, offering enhanced functionality compared to standard embeds.

### Key Features

- **Performance**: Preconnections for faster rendering
- **Metadata**: Automatic loading of titles and posters
- **Loading Control**: Complete control via load strategies (lazy-loaded by default)
- **Reliability**: Autoplay failure detection
- **Privacy**: No cookies by default for GDPR compliance
- **Quality**: Vimeo Pro detection for playback speed and quality options
- **Integration**: Full access to all Vidstack Player features

### Supported Source Formats

The following URL patterns are recognized:

- `vimeo/640499893`
- `https://vimeo.com/640499893`
- `https://player.vimeo.com/video/640499893`

The numeric ID (e.g., `640499893`) represents the video's unique identifier, found in Vimeo video URLs.

### Hash Parameter

For private embeds, append a hash to any valid source URL to include access credentials.

### Configuration

The provider supports specific embed options that can be customized during setup. Refer to the Player component documentation for available props, methods, and events.

---

## Google Cast Provider

The Google Cast provider enables remote playback capabilities through the Google Cast framework, allowing media to be played on remote devices such as televisions. The provider is automatically lazy loaded when users select a cast receiver.

### Configuration

The `googleCast` player property allows you to configure the receiver application ID. Additional customization is available through the provider setup hook, where you can further configure:

- Cast context
- Session settings
- Media properties
- Player options

### Active Screen Display

When Google Cast is actively connected to a receiver, the player displays a black screen within the `MediaProvider` component featuring the Google Cast logo and the connected device name.

### Event Handling

#### Available Events

The player component supports the following Google Cast events:

| Event | Type |
|-------|------|
| `onGoogleCastLoadStart` | function |
| `onGoogleCastLoaded` | function |
| `onGoogleCastPromptClose` | function |
| `onGoogleCastPromptError` | function |
| `onGoogleCastPromptOpen` | function |

#### Error Handling

Error events, particularly `onGoogleCastPromptError`, allow you to listen for and manage Google Cast prompt errors during the casting process.

#### Event Type Naming Convention

All cast event types follow the naming pattern: `GoogleCast[EventName]Event`

### Related Resources

For comprehensive information on remote playback functionality and tracking changes, refer to the [Remote Playback API documentation](https://vidstack.io/docs/player/api/remote-playback).

---

## Remotion Provider

The Remotion provider allows you to embed, preview, and play dynamic React components that are using Remotion. This integration enables playback of videos built programmatically with React through the Remotion framework.

### Source Configuration

The `src` player property accepts the same video options as the Remotion Player library.

### Available Options

| Option | Type | Default |
|--------|------|---------|
| `src` | `React.ReactNode` | -- |
| `type` | `string` | -- |
| `compositionWidth` | `number` | `1920` |
| `compositionHeight` | `number` | `1080` |
| `fps` | `number` | `30` |
| `durationInFrames` | `number` | -- |
| `initialFrame` | `number` | `0` |
| `inFrame` | `number \| null` | `0` |
| `outFrame` | `number \| null` | `durationInFrames` |
| `numberOfSharedAudioTags` | `number \| undefined` | `5` |
| `inputProps` | `RemotionInputProps` | `{}` |
| `renderLoading` | `RemotionLoadingRenderer` | `undefined` |
| `errorFallback` | `RemotionErrorRenderer` | `undefined` |
| `onError` | `(error: Error) => void` | `undefined` |

### Available Components

Three specialized components are provided for Remotion integration:

- `RemotionPoster`
- `RemotionThumbnail`
- `RemotionSliderThumbnail`

Each component has dedicated documentation covering usage and styling details.
