---
name: vidstack
description: VidStack Player 完整開發指南。涵蓋視頻播放器架構、React 組件、Hooks API、HLS/DASH/YouTube/Vimeo/Bunny 串流整合、字幕軌道、畫質切換、全螢幕、畫中畫、無障礙功能。當需要實作或修改視頻播放器功能、處理媒體串流、自訂播放器 UI 或整合第三方視頻服務時，請啟用此技能。
metadata:
  domain: vidstack-player
  version: "1.2"
compatibility: "vidstack ^1.x, @vidstack/react ^1.x, hls.js ^1.x"
---

# VidStack Player — Complete Development Guide

## Overview

VidStack is a modern, framework-agnostic media player library (MIT licensed). This project uses the **React** integration (`@vidstack/react`). Key characteristics:

- **Bundle size**: ~54kB gzip (core), significantly smaller than Video.js (195kB)
- **Reactivity**: Built on Signals (maverick.js) for fine-grained state tracking
- **TypeScript**: First-class type safety across all APIs
- **Providers**: Audio, Video, HLS, DASH, YouTube, Vimeo, Google Cast, Remotion
- **Accessibility**: WCAG 2.1, FCC/CVAA compliant with ARIA roles, keyboard controls, captions

> For detailed reference on any topic below, see the **Reference Files** section at the bottom.

---

## Architecture

### Player/Provider Pattern

```
MediaPlayer (state manager, event dispatcher)
  └── MediaProvider (renders the actual <video>/<audio>/iframe)
        └── Provider Loader (selects correct provider based on src)
```

- **MediaPlayer** — Root component. Manages all media state, dispatches events, handles requests, exposes state via HTML data attributes and CSS properties.
- **MediaProvider** — Renders the underlying media element. Automatically selects the correct provider (native video, HLS via hls.js, YouTube iframe, etc.) based on the `src` prop.

### State Management Model

VidStack uses a **signal-based reactive system** (maverick.js):

```tsx
import { useMediaState, useMediaStore, useMediaRemote } from "@vidstack/react";

// Read a single state property (re-renders only when this value changes)
const paused = useMediaState("paused");

// Read multiple state properties
const { paused, currentTime, duration } = useMediaStore();

// Dispatch playback commands
const remote = useMediaRemote();
remote.play();
remote.seek(30);
remote.toggleFullscreen();
```

### Request/Response Flow

1. UI dispatches a **request event** (e.g., `media-play-request`) via `MediaRemoteControl`
2. **Request Manager** validates and processes the request
3. Provider executes the action
4. **State Manager** updates reactive state
5. **Media events** fire (e.g., `onPlay`, `onPlaying`)

---

## Installation (React)

```bash
npm i vidstack @vidstack/react
# For HLS streaming:
npm i hls.js
# For DASH streaming:
npm i dashjs
```

### Basic Setup

```tsx
import { MediaPlayer, MediaProvider } from "@vidstack/react";
import "@vidstack/react/player/styles/base.css";
// Optional: default theme
import "@vidstack/react/player/styles/default/theme.css";
import "@vidstack/react/player/styles/default/layouts/video.css";
import {
  defaultLayoutIcons,
  DefaultVideoLayout,
} from "@vidstack/react/player/layouts/default";

function Player() {
  return (
    <MediaPlayer title="My Video" src="https://example.com/video.mp4">
      <MediaProvider />
      <DefaultVideoLayout icons={defaultLayoutIcons} />
    </MediaPlayer>
  );
}
```

### Load Strategies

Control when media metadata loads via `load` prop on `<MediaPlayer>`:

| Strategy | Description |
|----------|-------------|
| `eager` | Load immediately |
| `idle` | Load when browser is idle (`requestIdleCallback`) |
| `visible` | Load when player enters viewport (IntersectionObserver) |
| `play` | Load only when user initiates play (default on mobile) |
| `custom` | Manual control via `player.startLoading()` |

---

## Component Taxonomy

### Core Components

| Component | Import | Purpose |
|-----------|--------|---------|
| `MediaPlayer` | `@vidstack/react` | Root player, manages state & events |
| `MediaProvider` | `@vidstack/react` | Renders underlying media element |

### Layout Components

| Component | Purpose |
|-----------|---------|
| `DefaultAudioLayout` / `DefaultVideoLayout` | Production-ready layouts with full controls |
| `PlyrLayout` | Plyr-style layout alternative |

### Display Components

| Component | Purpose |
|-----------|---------|
| `Poster` | Display image before/during load |
| `Controls` / `Controls.Group` | Auto-hiding control container |
| `Captions` | Render text tracks/subtitles |
| `Gesture` | Map pointer/touch/keyboard to actions |
| `Time` | Display current/duration/remaining time |
| `Thumbnail` | Preview thumbnails (VTT sprite sheets) |
| `Title` / `ChapterTitle` | Display media/chapter title |
| `BufferingIndicator` | Show loading state |
| `Track` | Declaratively add text tracks |
| `Announcer` | Screen reader announcements |
| `Icons` | Icon set (from `@vidstack/react/icons`) |

### Button Components

| Component | Purpose |
|-----------|---------|
| `PlayButton` | Toggle play/pause |
| `MuteButton` | Toggle mute |
| `CaptionButton` | Toggle captions |
| `FullscreenButton` | Toggle fullscreen |
| `PIPButton` | Toggle picture-in-picture |
| `SeekButton` | Seek forward/backward by seconds |
| `LiveButton` | Seek to live edge |
| `AirPlayButton` | Start AirPlay |
| `GoogleCastButton` | Start Google Cast |
| `ToggleButton` | Generic toggle button base |
| `Tooltip` | Tooltip wrapper for buttons |

### Slider Components

| Component | Purpose |
|-----------|---------|
| `TimeSlider` | Seek through media timeline |
| `VolumeSlider` | Adjust volume |
| `SpeedSlider` | Adjust playback rate |
| `QualitySlider` | Select video quality |
| `AudioGainSlider` | Adjust audio gain |
| `Slider` | Base slider component |

Sub-components: `.Root`, `.Thumb`, `.Track`, `.TrackFill`, `.Preview`, `.Value`, `.Steps`
TimeSlider extras: `.Progress`, `.Chapters`, `.Video` (preview video)

### Menu Components

| Component | Purpose |
|-----------|---------|
| `Menu` | Dropdown menu (`.Root`, `.Button`, `.Items`, `.Item`, `.Portal`) |
| `Menu.RadioGroup` / `Menu.Radio` | Radio selection within menus |
| `RadioGroup` | Standalone radio group |

---

## API Quick Reference

### Key Player Props

```tsx
<MediaPlayer
  src="video.mp4"           // Source URL or array of sources
  poster="poster.jpg"       // Poster image
  title="My Video"          // Media title
  autoPlay={false}          // Autoplay (muted recommended)
  muted={false}             // Mute audio
  volume={1}                // 0-1
  playbackRate={1}          // Speed multiplier
  loop={false}              // Loop playback
  crossOrigin="anonymous"   // CORS setting
  clipStartTime={0}         // Clip start (seconds)
  clipEndTime={0}           // Clip end (seconds)
  load="visible"            // Load strategy
  logLevel="warn"           // Logging level
  keyTarget="player"        // Keyboard event target
  fullscreenOrientation="landscape" // Orientation lock on fullscreen
  storage="my-player"       // Persist user preferences (localStorage key)
>
```

### Autoplay

```tsx
// Muted autoplay (best chance of working)
<MediaPlayer autoPlay muted src="video.mp4">

// Events
<MediaPlayer
  onAutoPlay={(detail) => console.log(detail)}       // autoplay succeeded
  onAutoPlayFail={(detail) => console.log(detail)}    // autoplay failed
>
```

### Fullscreen

```tsx
const remote = useMediaRemote();
remote.enterFullscreen();     // or 'prefer-media' target
remote.exitFullscreen();
remote.toggleFullscreen();

// Lock orientation on fullscreen
<MediaPlayer fullscreenOrientation="landscape">
```

### Picture-in-Picture

```tsx
remote.enterPictureInPicture();
remote.exitPictureInPicture();
remote.togglePictureInPicture();

const pip = useMediaState("pictureInPicture");        // boolean
const canPip = useMediaState("canPictureInPicture");  // boolean
```

### Text Tracks & Captions

```tsx
import { Track } from "@vidstack/react";

<MediaPlayer src="video.mp4">
  <MediaProvider>
    <Track src="subs-en.vtt" kind="subtitles" label="English" lang="en" default />
    <Track src="chapters.vtt" kind="chapters" lang="en" default />
  </MediaProvider>
  <Captions />  {/* Renders active text track */}
</MediaPlayer>
```

Hooks for text tracks:
- `useActiveTextTrack(kind)` — get active track of a kind
- `useActiveTextCues(track)` — get currently active cues
- `useTextCues(track)` — get all cues on a track
- `createTextTrack(options)` — programmatically create a track
- `useCaptionOptions()` — get caption toggle options for menus

### Video Quality

```tsx
const qualities = useMediaState("qualities");
const autoQuality = useMediaState("autoQuality");

remote.changeQuality(index);      // Switch to specific quality
remote.requestAutoQuality();       // Enable auto quality

// Quality options for menus
const options = useVideoQualityOptions({ sort: "descending" });
```

### Audio Tracks

```tsx
const audioTracks = useMediaState("audioTracks");
remote.changeAudioTrack(index);

const options = useAudioOptions();  // For menus
```

### Keyboard Shortcuts

```tsx
<MediaPlayer
  keyTarget="player"    // 'player' | 'document'
  keyShortcuts={{
    togglePaused: "k Space",
    toggleMuted: "m",
    toggleFullscreen: "f",
    seekBackward: "ArrowLeft j",
    seekForward: "ArrowRight l",
    volumeUp: "ArrowUp",
    volumeDown: "ArrowDown",
    toggleCaptions: "c",
  }}
>
```

---

## Hooks Quick Reference

### Core Hooks

| Hook | Returns | Purpose |
|------|---------|---------|
| `useMediaPlayer()` | `MediaPlayerInstance \| null` | Get nearest player instance |
| `useMediaProvider()` | `MediaProviderAdapter \| null` | Get current provider |
| `useMediaRemote()` | `MediaRemoteControl` | Dispatch playback commands |
| `useMediaState(prop)` | `T` | Subscribe to single state property |
| `useMediaStore()` | `MediaState` | Subscribe to all state properties |

### Slider Hooks

| Hook | Returns | Purpose |
|------|---------|---------|
| `useSliderState(prop)` | `T` | Subscribe to slider state property |
| `useSliderStore()` | `SliderState` | Subscribe to all slider state |
| `useSliderPreview()` | `{ previewRootRef, previewRef, previewValue }` | Floating preview panel |

### Component State Hooks

| Hook | Returns | Purpose |
|------|---------|---------|
| `useState(ctor, prop, ref)` | `T[R]` | Subscribe to component state property |
| `useStore(ctor, ref)` | `Readonly<T>` | Subscribe to component store |

### Content Hooks

| Hook | Returns | Purpose |
|------|---------|---------|
| `useChapterTitle()` | `string` | Current chapter title |
| `useThumbnails(src)` | `VTTCue[]` | Load thumbnail sprites |
| `createTextTrack(init)` | `TextTrack` | Create & add text track |
| `useTextCues(track)` | `VTTCue[]` | All cues on a track |
| `useActiveTextCues(track)` | `VTTCue[]` | Currently active cues |
| `useActiveTextTrack(kind)` | `TextTrack \| null` | Active track of a kind |

### Menu Option Hooks

| Hook | Returns | Purpose |
|------|---------|---------|
| `useAudioOptions()` | `AudioOption[]` | Audio track selection |
| `useAudioGainOptions(gains)` | `AudioGainOption[]` | Audio gain presets |
| `useCaptionOptions()` | `CaptionOption[]` | Caption track selection |
| `useChapterOptions()` | `ChapterOption[]` | Chapter navigation |
| `usePlaybackRateOptions(rates)` | `PlaybackRateOption[]` | Speed selection |
| `useVideoQualityOptions(opts)` | `VideoQualityOption[]` | Quality selection |

---

## Provider Overview

| Provider | Source Format | Library Required | Use Case |
|----------|-------------|-----------------|----------|
| Audio | `.mp3`, `.m4a`, `.wav`, `.ogg`, `.flac` | None | Audio-only playback |
| Video | `.mp4`, `.webm`, `.ogg`, `.mov` | None | Standard video playback |
| HLS | `.m3u8` | `hls.js` | Adaptive streaming (most common) |
| DASH | `.mpd` | `dashjs` | Adaptive streaming (alternative) |
| YouTube | YouTube URLs (5 formats) | None | YouTube embeds |
| Vimeo | Vimeo URLs (3 formats) | None | Vimeo embeds |
| Google Cast | N/A | Cast SDK | Remote playback |
| Remotion | React component | `@remotion/player` | Dynamic React video |

### HLS Setup (Most Common in This Project)

```tsx
import { MediaPlayer, MediaProvider } from "@vidstack/react";

<MediaPlayer src="https://example.com/stream.m3u8">
  <MediaProvider />
</MediaPlayer>
```

hls.js is loaded automatically from JSDelivr by default. For custom loading:

```tsx
<MediaPlayer>
  <MediaProvider
    onProviderChange={(provider) => {
      if (provider?.type === "hls") {
        provider.library = () => import("hls.js");
        // or a CDN URL string
      }
    }}
    onProviderSetup={(provider) => {
      if (provider.type === "hls") {
        // Access hls.js instance
        provider.instance; // Hls instance
        // Configure hls.js
        provider.config = { /* hls.js config */ };
      }
    }}
  />
</MediaPlayer>
```

### YouTube Setup

```tsx
// Supported URL formats:
// https://www.youtube.com/watch?v=VIDEO_ID
// https://youtu.be/VIDEO_ID
// https://www.youtube.com/embed/VIDEO_ID
// https://www.youtube-nocookie.com/embed/VIDEO_ID
// https://www.youtube.com/shorts/VIDEO_ID

<MediaPlayer src="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
  <MediaProvider />
</MediaPlayer>
```

### Vimeo Setup

```tsx
// Supported URL formats:
// https://vimeo.com/VIDEO_ID
// https://player.vimeo.com/video/VIDEO_ID
// https://vimeo.com/VIDEO_ID?h=HASH (private videos)

<MediaPlayer src="https://vimeo.com/640499893">
  <MediaProvider />
</MediaPlayer>
```

---

## Styling

### Data Attributes

VidStack exposes media state as HTML data attributes for CSS styling:

```css
/* Style based on playback state */
media-player[data-paused] .my-element { /* paused */ }
media-player[data-playing] .my-element { /* playing */ }
media-player[data-waiting] .my-element { /* buffering */ }
media-player[data-fullscreen] .my-element { /* fullscreen */ }
media-player[data-pip] .my-element { /* picture-in-picture */ }
media-player[data-captions] .my-element { /* captions visible */ }
media-player[data-muted] .my-element { /* muted */ }

/* Stream type */
media-player[data-view-type="video"] { /* video view */ }
media-player[data-stream-type="live"] { /* live stream */ }
media-player[data-stream-type="live:dvr"] { /* live with DVR */ }
```

### Tailwind CSS Plugin

```js
// tailwind.config.js
module.exports = {
  plugins: [require("vidstack/tailwind.cjs")],
};
```

Tailwind variants: `media-paused:`, `media-playing:`, `media-muted:`, `media-fullscreen:`, `media-captions:`, `media-buffering:`, `media-can-play:`, `media-live:`, `media-type:video`, `media-type:audio`

### Responsive Design

Use CSS Container Queries (recommended) or Media Queries:

```css
@container (min-width: 640px) {
  /* Desktop layout */
}
@container (max-width: 639px) {
  /* Mobile layout */
}
```

---

## Events

### Key Media Events

```tsx
<MediaPlayer
  onPlay={() => {}}
  onPause={() => {}}
  onPlaying={() => {}}
  onWaiting={() => {}}
  onEnded={() => {}}
  onTimeUpdate={(detail) => { /* { currentTime, played } */ }}
  onVolumeChange={(detail) => { /* { volume, muted } */ }}
  onSeeking={(currentTime) => {}}
  onSeeked={(currentTime) => {}}
  onFullscreenChange={(isFullscreen) => {}}
  onPictureInPictureChange={(isActive) => {}}
  onTextTrackChange={(track) => {}}
  onQualityChange={(quality) => {}}
  onAutoPlayFail={(detail) => {}}
  onError={(detail) => {}}
  onProviderChange={(provider) => {}}
  onProviderSetup={(provider) => {}}
  onCanPlay={(detail) => {}}
  onLoadedMetadata={() => {}}
  onLoadedData={() => {}}
>
```

### Event Triggers

Every `DOMEvent` has a `triggers` chain to trace the origin:

```tsx
onPlay={(nativeEvent) => {
  // Was this triggered by a user click?
  const isUserAction = nativeEvent.isOriginTrusted;
  // Walk the trigger chain
  nativeEvent.triggers.walk((event) => {
    console.log(event.type); // e.g., 'pointerup' -> 'media-play-request' -> 'play'
  });
}}
```

---

## MediaRemoteControl

The `MediaRemoteControl` class dispatches request events to control the player:

```tsx
const remote = useMediaRemote();

// Playback
remote.play();
remote.pause();
remote.seekToLiveEdge();
remote.seek(timeInSeconds);

// Volume
remote.changeVolume(0.5);
remote.changeMuted(true);
remote.changeAudioGain(1.5);

// Tracks
remote.changeAudioTrack(index);
remote.changeTextTrackMode(index, mode); // 'showing' | 'hidden' | 'disabled'
remote.changeQuality(index);
remote.changePlaybackRate(1.5);

// Screen
remote.enterFullscreen(target?);   // 'prefer-media' | 'media' | 'provider'
remote.exitFullscreen(target?);
remote.enterPictureInPicture();
remote.exitPictureInPicture();

// Controls
remote.pauseControls();
remote.resumeControls();
remote.togglePaused();
remote.toggleMuted();
remote.toggleFullscreen(target?);
remote.togglePictureInPicture();
remote.toggleCaptions();
```

---

## Live Streaming

```tsx
<MediaPlayer
  src="https://example.com/live.m3u8"
  streamType="live"       // or "live:dvr" for DVR support
  liveEdgeTolerance={10}  // seconds from live edge
>
```

State:
- `useMediaState("live")` — is live stream
- `useMediaState("liveEdge")` — is at live edge
- `useMediaState("liveEdgeWindow")` — DVR window size

```tsx
remote.seekToLiveEdge(); // Jump to live edge
```

---

## Reference Files

Detailed documentation is split into reference files. Read the appropriate file when you need in-depth information:

| File | Content | When to Read |
|------|---------|--------------|
| `references/getting-started.md` | Overview, installation, architecture, accessibility, loading, events, state management, styling, responsive design, Tailwind | Setting up a new player, understanding architecture, styling questions |
| `references/api-core-features.md` | Autoplay, fullscreen, PiP, screen orientation, live, keyboard, audio gain, audio tracks, remote playback, text tracks, video quality | Implementing specific player features |
| `references/api-providers.md` | Audio, Video, HLS, DASH, YouTube, Vimeo, Google Cast, Remotion providers | Integrating media sources, configuring streaming |
| `references/api-classes-helpers.md` | MediaRemoteControl (35+ methods), DOMEvent, EventTriggers, helper functions | Custom player controls, event handling, debugging |
| `references/api-hooks.md` | All 22 React hooks with signatures, parameters, return types, source code | Building custom React components that interact with the player |
| `references/components-core-layouts.md` | MediaPlayer props/events/state, MediaProvider, DefaultLayout slots, PlyrLayout | Configuring the player root, customizing layouts |
| `references/components-display.md` | Announcer, Poster, Controls, Gesture, Icons, Captions, Thumbnail, Time, Track, Title, ChapterTitle, BufferingIndicator | Building custom player UI overlays |
| `references/components-buttons.md` | ToggleButton, PlayButton, MuteButton, CaptionButton, PIPButton, FullscreenButton, LiveButton, SeekButton, AirPlayButton, GoogleCastButton, Tooltip | Customizing player control buttons |
| `references/components-sliders.md` | Slider, AudioGainSlider, TimeSlider, VolumeSlider, SpeedSlider, QualitySlider + all sub-components | Building custom sliders, timeline, volume controls |
| `references/components-menus-remotion.md` | Menu, RadioGroup, RemotionPoster, RemotionThumbnail, RemotionSliderThumbnail | Building settings menus, Remotion integration |
