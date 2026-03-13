# VidStack Player - Getting Started & Core Concepts

## Overview

Vidstack Player is a comprehensive framework and component collection for creating custom media players on the web. Users can either build their own player or utilize the production-ready Default Layout, customizing it to match their specific branding needs.

### Why Use This Library?

Building a production-grade media player involves addressing several complex challenges:

- **State Management**: Coordinating state across providers, browsers, and stream types while connecting to the UI layer requires careful architecture to avoid performance issues and fragile code patterns.
- **Accessibility**: Implementation requires proper ARIA roles, focus management, keyboard controls, touch targets, tooltip positioning, and consistent caption rendering.
- **Styling**: Managing media state attributes for CSS styling, handling unsupported components, and accommodating different stream types (VOD/Live/Live DVR) adds complexity.
- **Provider Management**: Different providers offer distinct APIs, requiring abstraction for native media, embedded content, HLS, and DASH support with seamless provider switching.
- **Cross-Browser Compatibility**: Ensuring consistent functionality across browsers for features like fullscreen, picture-in-picture, captions, and streaming requires handling varying API support.
- **Tracking**: Implementing analytics and monitoring requires a well-designed event system that distinguishes between user interactions and system events.

### Target Audience

The library serves both individual developers building blogs and podcasts, and enterprise applications requiring scalable media experiences. Its design emphasizes minimal core requirements with optional component inclusion, supporting multiple frameworks through Web Components and React (including Next.js 13 and React Server Components).

### Features

- First-class TypeScript support across all frameworks
- Multiple framework support: Web Components, React, Vue, Svelte, Solid
- CSS, Tailwind CSS, and customizable Default Theme styling options
- Multi-provider support: Audio, Video, HLS, DASH, YouTube, Vimeo, Remotion
- Unified API across all providers
- Production-ready layouts
- HLS and live streaming support
- 30+ customizable components
- 18+ React hooks for state management
- Keyboard shortcuts and gesture support
- AirPlay and Google Cast support
- Accessibility compliance with WCAG 2.1 and FCC/CVAA guidelines
- Custom media captions library (VTT, SRT, SSA support)
- Internationalization (i18n) support
- Minimal bundle size: 54kB gzip for core features

### Comparison with Alternatives

Key differentiators from competitors like JW Player and Video.js:

- **TypeScript**: Comprehensive type safety throughout
- **Reactivity**: Built on Signals for fine-grained state tracking
- **Components**: Extensive declarative component library
- **Custom UI**: Better-suited for building custom player interfaces
- **Production-ready UI**: Higher-quality default experience with extensive customization via CSS variables
- **Framework Support**: Broader framework and environment compatibility
- **Rich Events**: More events with origin tracking capabilities
- **Modern Approach**: Current APIs, ESM bundles, and deprecated browser support removed
- **Bundle Size**: Significantly smaller than competitors (Video.js: 195kB gzipped vs. Vidstack: 53.4kB gzipped)
- **Licensing**: MIT licensed with unlimited usage (vs. paid models)

---

## Installation

### 1. Select Framework

The player supports multiple frameworks:

- **JavaScript** -- Vanilla JavaScript implementation
- **Angular** -- Angular framework integration
- **React** -- React component library
- **Svelte** -- Svelte framework support
- **Vue** -- Vue framework integration
- **Solid** -- Solid.js framework support
- **Web Components** -- Framework-agnostic web components
- **CDN** -- Direct script tag inclusion

### 2. Select Provider

Choose your media source type:

- **Audio** -- Audio file streaming
- **Video** -- Standard video playback
- **HLS** -- HTTP Live Streaming protocol
- **DASH** -- Dynamic Adaptive Streaming over HTTP
- **YouTube** -- YouTube video embedding
- **Vimeo** -- Vimeo video integration
- **Remotion** -- Remotion composition support

### 3. Select Styling

A layout refers to the arrangement and presentation of various player components. The CSS and Tailwind CSS options are for styling components from scratch and building your own layout. The Default Theme option is for building your own layout on top of the component styles.

Available styling approaches:

- **CSS** -- Custom stylesheet approach
- **Default Theme** -- Pre-styled component base
- **Default Layout** -- Production-ready template
- **Plyr Layout** -- Alternative production template
- **Tailwind CSS** -- Utility-first CSS framework

---

## Architecture

### Request-Response Model

The Vidstack Player implements a request-response model comparable to client-server communication. State exists as signals, distributed downward through context to UI components that subscribe via effects. DOM events flow upward to request state changes, and the media provider responds asynchronously by fulfilling or declining requests.

State is stored as signals and pushed down via context to consumers such as UI components who subscribe to updates via effects. DOM events are dispatched up to the player for requesting state changes.

When requesting player actions, success is not guaranteed from the requestor's perspective. The `MediaRequestManager` receives these requests from child components and coordinates with the current media provider. The `MediaProviderAdapter` standardizes communication across different provider types. After processing, requests enter a queue pending async provider response. Once the provider notifies the `MediaStateManager` of completion or failure, the request is satisfied by attaching to the relevant media event, released from queue, and the media store updates.

### Source Selection

The source selection determines which provider loader activates and which provider to load:

1. Detect `src` attribute or property changes
2. Normalize `src` into `Src` objects with structure `{src, type}`
3. Fire the `sources-change` event
4. Iterate through sources sequentially, testing each provider's `canPlay` method
5. Fire `source-change` event once a provider matches (or `null` if none match)
6. Initiate provider loading

### Media Provider Loader

Provider loaders decide if they can handle a source, dynamically load the provider, and render appropriate elements (`<audio>`, `<video>`, `<iframe>`).

When activated, the loader follows this sequence:

1. Destroy the previous provider and fire `provider-change` with `null` detail
2. Attempt preconnecting relevant URLs
3. Fire `provider-loader-change` event
4. Wait for the loader to render the underlying media element
5. Dynamically import and initialize the provider
6. Fire `provider-change` event (optimal timing for configuration)
7. Call provider `setup` method after the player's loading strategy resolves
8. Fire `provider-setup` event
9. Call `loadSource` on the provider with the selected source

If the provider remains unchanged during source switching, setup is skipped; only the new source loads.

### Media Provider

Providers handle rendering the media element or iframe, determining media and view types, loading sources, managing tracks, setting properties, processing requests, attaching listeners, and notifying the `MediaStateManager` of changes. They implement the `MediaProviderAdapter` interface for API consistency.

### Media Context

The media context is a singleton object passed from each player instance to all consumers. It contains important objects such as:

- The player itself
- Remote control for dispatching requests
- Player delegate for notifying the state manager of updates
- Media store for UI to subscribe to state changes
- The current media provider

### Media Store

The media store comprises signals tracking individual state pieces. The `MediaStateManager` updates the store when the provider signals changes. Player components subscribe to these state updates through effects for rendering, DOM management, and operations.

Signals function as reactive observables for storing state, creating computed properties, and subscribing to value changes. Vidstack created Maverick Signals, their own signals library, for managing scoping and reactivity complexity.

### UI Components

UI components are abstraction-based, avoiding code duplication across Custom Elements and React implementations. Built on Maverick component library, they leverage Signals as their reactivity primitive, enabling framework-agnostic adaptation.

Component lifecycle simplified to:

- `onSetup` -- initial
- `onAttach` -- DOM/server attached
- `onConnect` -- DOM connected
- `onDestroy` -- lifecycle end

These hooks are pure and individually disposable.

Base components define contracts for props, state, and events. They provide no UI initially but handle accessibility, data attributes, CSS variables, prop management, media state subscription, event listener attachment, event dispatching, and method exposure.

- `Host(Component, HTMLElement)` creates Custom Elements
- `createReactComponent(Component)` creates React components

### Media Remote Control

The `MediaRemoteControl` simplifies dispatching media request events. Rather than manually creating events like `el.dispatchEvent(new DOMEvent('media-play-request'))`, consumers call convenience methods like `remote.play()`.

### Media Request Manager

The `MediaRequestManager` routes media request events to the current provider by invoking appropriate actions. It queues requests so the `MediaStateManager` can satisfy them by attaching to correct media events. The `MediaProviderAdapter` enables provider-agnostic operation.

### Media State Manager

The `MediaStateManager` handles provider-delegated state changes, satisfies media request events by attaching them as triggers to success/failure events, releases queued requests, dispatches media events, and maintains media store synchronization with current playback and provider state.

---

## Accessibility

Providing an accessible experience requires effort from the developer's end too, requiring review of implementation, thorough testing, and gathering user feedback.

The player addresses accessibility through multiple approaches:

- Clear ARIA roles, labels, and relationships
- Alternative text for visual content
- Proper color contrast standards
- Light/dark mode support
- Keyboard controls and navigation
- Touch-friendly target zones
- Captions, subtitles, and audio descriptions
- Reduced motion support
- Customizable playback options
- Screen reader announcements
- Comprehensive documentation

### Guidelines & Standards

Vidstack adheres to four primary accessibility frameworks:

- **WAI-ARIA 1.2** -- W3C technical specifications defining roles, states, and properties for assistive technologies
- **WCAG 2.2** -- Standards providing recommendations for accessible web content across varying user abilities
- **CVAA** -- U.S. federal law requiring closed captioning for video content and accessible telecommunications services
- **ADA** -- Civil rights legislation mandating website accessibility for individuals with hearing, vision, or physical disabilities

### Key Features

**Labels & ARIA**: Implements descriptive labels, aria-label/labelledby attributes, aria-hidden for irrelevant content, appropriate roles, and proper language specifications.

**Captions**: Custom caption rendering supporting VTT, text, SRT, SSA, and JSON formats with consistent browser and provider customization.

**Audio Descriptions**: Multiple audio track support through HLS playlists with automatic integration into Default Layout settings menus.

**Keyboard Navigation**: Full keyboard operability for all controls with focus indicators and standardized shortcuts.

**Focus Management**: Logical focus redirection during state changes to assist screen reader users -- for example, focusing the selected option when opening radio menus.

---

## Loading

### Sizing

The browser uses the intrinsic size of loaded media by default, which can cause layout shifts as the player transitions from default to actual dimensions. To prevent this, set the aspect ratio to match your media's intrinsic ratio, or specify explicit width/height using CSS.

### Load Strategies

Loading strategies determine when media or poster images begin loading:

- **eager**: Load immediately for content requiring instant interactivity
- **idle**: Load after page completion via `requestIdleCallback`
- **visible**: Load when entering the visual viewport (below-the-fold content)
- **play**: Load provider and media on user interaction
- **custom**: Use `startLoading()` method or `media-start-loading` event for fine-grained control

Poster loading operates independently from media loading, enabling thumbnail display before playback readiness.

### View Type

The view type suggests media layout as either `audio` or `video`, informing how layouts display controls and UI elements. The player infers this from the provider by default, but you can specify it explicitly.

### Stream Type

Stream type defines content delivery mode:

- **on-demand**: Pre-recorded VOD with full playback control (pause, rewind, fast-forward)
- **live**: Real-time streaming with limited viewer control
- **live:dvr**: Live content with DVR capabilities (pause, rewind, fast-forward)
- **ll-live**: Low-latency live for near-real-time viewing
- **ll-live:dvr**: Low-latency with DVR features

Specifying the type ensures accurate state management and appropriate UI presentation.

### Duration

Provide explicit duration when known to avoid rounding errors and ensure correct UI state without awaiting metadata loading.

### Clipping

Clipping shortens media by specifying start and end times. The duration and chapter durations adjust accordingly. Media resources like text tracks and thumbnails should use the full duration. Seeking operates on clipped duration (e.g., a 1-minute clipped video to 30 seconds makes seeking to 30s the endpoint). URI fragments are set internally for efficient file loading.

### Media Session

The Media Session API automatically activates using provided `title`, `artist`, and `artwork` properties (with `poster` as fallback).

### Storage

Storage preserves player settings across sessions, including language, volume, muted state, captions visibility, and playback time.

**Local Storage**: Saves data to the user's browser -- simple but limited to a single domain, device, and browser.

**Remote Storage**: Enables asynchronous, persistent data management across domains and devices by implementing the `MediaStorage` interface.

### Sources

The player accepts single or multiple sources as URL strings or objects (`MediaStream`, `MediaSource`, `Blob`, `File`). Multiple formats should cover all target browsers.

#### Source Types

File extensions or type hints determine provider and playback method:

Valid formats:
- `src="https://example.com/video.mp4"`
- `src="https://example.com/hls.m3u8"`
- `src="https://example.com/dash.mpd"`
- `src = { src: "url", type: "video/mp4" }`

Invalid formats lack both extension and type hint.

#### Source Sizes

Multiple video files with different resolutions enable quality selection, though adaptive streaming protocols like HLS are recommended.

#### Supported Codecs

Browser runtime capabilities determine codec support. Review MDN documentation for media containers and audio/video codecs before implementation.

### Providers

Auto-selected during source selection, then dynamically loaded:

- Audio, Video, HLS, DASH, YouTube, Vimeo, Remotion, Google Cast

Provider events fire as providers change or initialize.

### Audio Tracks

Audio tracks load from HLS playlists. Manual addition is unsupported. Use the Audio Tracks API for programmatic interaction.

### Text Tracks

Text tracks provide text-based information for video/audio enhancement.

**Formats supported**: VTT, SRT, SSA/ASS, JSON

**Track kinds**:

- **subtitles**: Audio translation for non-native speakers
- **captions**: Dialog plus audio descriptions (music, effects)
- **chapters**: Section information with start times
- **descriptions**: Visual content for blind/visually impaired users
- **metadata**: Background information for SEO or supplementary details

Set `default` on a track to immediately activate it (showing mode).

#### JSON Tracks

JSON can be provided directly or loaded remotely, supporting VTT cues, regions, and custom structures.

#### LibASS

A WASM port of libass enables advanced ASS features. Install `jassub`, copy its dist directory to public assets, and add `LibASSTextRenderer` to the player.

### Thumbnails

Static images or frames enabling visual content preview and navigation. Display in time sliders or menus.

#### VTT Format

WebVTT files specify time ranges, image URLs, and optional sprite coordinates. Sprites (large storyboard images) reduce file size and server requests compared to individual images.

#### JSON Format

JSON thumbnails support VTT cues, image arrays, or storyboard structures, with Mux storyboards supported natively.

#### Object Format

Provide thumbnail objects directly with multiple images or storyboard data.

### Video Qualities

Adaptive streaming protocols (HLS, DASH) deliver chunked media with quality adaptation based on device size and network conditions. Streaming platforms like Cloudflare Stream and Mux auto-generate multiple renditions.

The streaming engine automatically selects optimal quality (displayed as "Auto"), but can be manually overridden. Access the Video Qualities API for programmatic quality interaction.

---

## Events

### Media Events

The player provides comprehensive media events that can be accessed through the Player API Reference. Vidstack smooths cross-browser inconsistencies and enriches events with additional metadata, including the request that triggered them and origin event information.

### Media Request Events

Vidstack Player operates on a request-response model for state updates. Requests are dispatched as events to the player component, which then performs corresponding operations on the provider.

**Request Example:** The `media-play-request` event asks the provider to begin or resume playback, triggering a `play()` call. The provider responds with either `play` or `play-fail` events.

#### When Requests Fire

Media request events are typically triggered by user interactions with Vidstack components, such as button clicks or slider drags. Some requests may be indirect, resulting from actions like scrolling the player out of view, switching tabs, or device sleep mode.

#### How Requests Fire

Request events are standard DOM events that can be dispatched like any other. The recommended approach uses the `MediaRemoteControl` for simplicity. Best practice involves attaching event triggers to trace requests back to their origin -- the method Vidstack components use internally.

### Cancelling Requests

Media request events can be prevented by listening on the player or dispatching component and blocking default behavior.

### Event Triggers

Events maintain a history of trigger events as a chain, with each event pointing to its predecessor up to the origin event. For example, clicking a play button creates a chain: button click -> play request -> play event.

Refer to event trigger helpers documentation for inspecting and traversing event chains.

### Event Types

All event types use PascalCase naming with an "Event" suffix (e.g., `SliderDragStartEvent`). Media-related events are prefixed with "Media." Component documentation pages detail their specific events.

---

## State Management

### Reading State

The `useMediaState` and `useMediaStore` hooks allow you to subscribe directly to specific media state changes rather than listening to multiple DOM events.

**Why use hooks instead of events:** Tracking media state through events is error-prone and requires tedious manual binding. The hooks provide a cleaner, more reliable approach.

**Using hooks in player context:** You can omit the ref parameter when calling these hooks inside a player child component since the media context is automatically available.

A complete list of all media states is available in the Player State Reference.

#### Avoiding Renders

For expensive or unnecessary state updates, you can subscribe directly to state changes on the player instance without triggering component re-renders.

**Within player child components:** Use `useMediaPlayer` to obtain a player instance reference for direct subscriptions.

### Updating State

The `useMediaRemote` hook creates and returns a `MediaRemoteControl` object. This class provides a facade for dispatching media request events, enabling you to:

- Request playback control (play/pause)
- Adjust volume levels
- Seek to specific time positions
- Perform other state-changing actions

**Event triggers:** All `MediaRemoteControl` methods accept event triggers, connecting media events to their origin. This helps track event sources and analyze timing data between requests and execution.

See the `MediaRemoteControl` API documentation for complete available methods.

---

## Styling Introduction

### Styling Elements

Vidstack Player allows you to style child elements using CSS based on current media state. Media state is exposed through data attributes on the player DOM element.

Use CSS attribute selectors `[attr]` combined with the `:not()` pseudo-class to create conditional styling based on media state. This enables powerful conditional selectors that respond to player states.

**Example pattern:**

```css
/* Style elements based on attribute presence/value */
[data-state="playing"] .control-button { /* styles */ }
[data-state]:not([data-state="paused"]) { /* styles */ }
```

See the Media Player Data Attributes reference for available attributes.

### Styling Components

Components expose both data attributes and CSS variables for customization. Each component page documents its available attributes and variables.

#### Default Theme

A built-in default theme is provided for rapid development. Apply it via classes to components:

```tsx
<MediaPlayer className="media-player">
  {/* components inherit default styles */}
</MediaPlayer>
```

Override CSS properties and modify CSS variables as needed for your design. Review player examples and component documentation for variable listings.

#### Animations

Hidden components (e.g., Tooltips, Menus) use `display: none` when inactive. The library respects CSS animations and waits for them to complete before performing operations like focusing elements or hiding components.

### Getting Started with Styling

Begin with these foundational elements:

- **Player**: Set width or aspect ratio to prevent layout shifts
- **Provider**: Understand its design role
- **Controls**: Style this grouped component first

Then explore additional components via the sidebar, organized by category.

---

## Responsive Design

### Sizing

Avoid layout shifts by preventing the player from jumping between its default size and intrinsic size. This practice is crucial for maintaining stable page layouts as network content loads.

### Layouts

A media player layout encompasses the arrangement and presentation of various elements and controls within a media player interface. These layouts determine component organization and how users interact with playback controls.

The library provides two pre-built, production-ready options:

- **Default Layout**
- **Plyr Layout**

Alternatively, developers can construct custom layouts by composing and styling individual player components, which offers greater control over UI presentation and arrangement.

### Responsive Query Strategies

#### CSS Media Queries

CSS Media Queries are a CSS feature used to apply styles based on the characteristics of the viewing device, such as screen size, orientation, or resolution. However, they have limitations for dynamic player adaptation.

#### Container Queries (Recommended)

CSS Container Queries are a web development feature that allows styles to adapt based on the dimensions and characteristics of a specific container element. These are preferable for media players because they enable responsive layouts based on the player's container size rather than viewport dimensions alone.

**Note:** Container Queries are a newer browser feature with evolving browser support.

---

## Tailwind CSS

### Overview

Vidstack provides a Tailwind CSS plugin that enables media state-based styling without requiring separate CSS files. This approach leverages utility classes for conditional styling based on player states.

### Installation

Register the plugin in `tailwind.config.js`:

```js
// tailwind.config.js
module.exports = {
  plugins: [
    require('vidstack/tailwind.cjs'),
  ],
};
```

### Media Variants

The plugin provides prefixed utilities that apply styling when specific media states are active. These variants can be negated with the `not-` prefix to invert conditions.

#### Playback States

Available variants:
- `media-autoplay`
- `media-buffering`
- `media-can-play`
- `media-ended`
- `media-error`
- `media-fullscreen`
- `media-live`
- `media-muted`
- `media-paused`
- `media-playing`
- `media-preview`
- `media-seeking`
- `media-started`
- `media-waiting`

#### Media & View Types

Distinguish between audio and video:
- `media-audio`
- `media-video`
- `media-view-audio`
- `media-view-video`

#### Stream Types

Identify stream characteristics:
- `media-stream-unknown`
- `media-stream-demand`
- `media-stream-live`
- `media-stream-dvr`
- `media-stream-ll`
- `media-stream-ll-dvr`

#### Remote Playback

Control AirPlay and Cast states:
- `media-can-air`
- `media-air`
- `media-cast`
- `media-can-cast`
- Plus connecting/disconnected variants

### Data Attributes

Components expose internal state via data attributes for conditional styling. Two key attributes:

- **`data-focus`**: Applied when components receive keyboard focus
- **`data-hocus`**: Applied during keyboard focus or pointer hover

These integrate directly with Tailwind's data attribute selectors for streamlined styling without class bloat.

### Usage Example

```html
<div class="media-paused:opacity-100 not-media-paused:opacity-0">
  <!-- Visible only when paused -->
</div>

<button class="data-[focus]:ring-2 data-[hocus]:bg-white/20">
  <!-- Focus and hover states -->
</button>
```
