# VidStack Player - Display Components

## Announcer

### Overview

The `MediaAnnouncer` component provides accessibility functionality by announcing important media state updates to screen readers. It communicates playback status, volume changes, caption toggles, fullscreen, picture-in-picture, and seeking events.

> This component is automatically included in the Default and Plyr layouts, so it should not be manually added when using those layouts.

### Import

```typescript
import { MediaAnnouncer, type MediaAnnouncerProps } from "@vidstack/react";
```

### Basic Usage

```jsx
<MediaAnnouncer />
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `translations` | `Partial<MediaAnnouncerTranslations>` | `null` | Customize announcement messages for screen readers |
| `ref` | `Ref<HTMLElement>` | - | Reference to underlying element |

Additionally accepts standard `HTMLAttributes`.

### Callbacks

| Callback | Type | Description |
|----------|------|-------------|
| `onChange` | `function` | Triggered on announcement state changes |

### Accessibility Features

The announcer covers these key player states:
- Playback status
- Volume adjustments
- Caption toggling
- Fullscreen transitions
- Picture-in-picture mode
- Seek operations

---

## Poster

### Overview

The Poster component loads and displays the current media poster or thumbnail image, typically shown before playback begins. By default, the media provider's loading strategy is respected, meaning the poster will not load until the media can.

### Import

```jsx
import { Poster } from "@vidstack/react";
```

### Basic Usage

```jsx
<MediaPlayer>
  <MediaProvider>
    <Poster src="..." alt="..." />
  </MediaProvider>
</MediaPlayer>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `alt` | `string` | `undefined` | Alternative text description for the poster image |
| `asChild` | `boolean` | `false` | When enabled, allows passing optimized image components (e.g., Next.js Image, Astro Image) as children |
| `children` | `ReactNode` | `null` | Child elements to render |
| `crossOrigin` | `mixed` | `null` | CORS attribute for cross-origin image loading |
| `src` | `string` | `null` | Source URL for the poster image |
| `ref` | `Ref<HTMLImageElement>` | - | React ref to the underlying image element |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Indicates when poster should be displayed |
| `data-loading` | Reflects loading state of the image |
| `data-error` | Indicates loading failures |
| `data-hidden` | Applied when no src exists or loading failed |

### Integration with Optimized Images

For server frameworks like Next.js and Astro, use the `asChild` property to integrate their optimized `<Image>` components directly into the Poster component.

---

## Controls

### Overview

The Controls component creates a container for control groups and manages their visibility timing in video players.

### Import

```tsx
import { Controls } from "@vidstack/react";
```

### Basic Usage

```tsx
<Controls.Root>
  <Controls.Group>
    {/* Control buttons and sliders */}
  </Controls.Group>
  <Controls.Group>
    {/* Additional controls */}
  </Controls.Group>
</Controls.Root>
```

### Controls.Root

Container for control groups with visibility management.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Child components |
| `hideDelay` | `number` | `2000` | Milliseconds before hiding controls |
| `hideOnMouseLeave` | `boolean` | `false` | Hide controls when mouse leaves |

#### Callbacks

| Callback | Description |
|----------|-------------|
| `onChange` | Triggered on visibility state changes |

#### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Indicates whether controls are visible |
| `data-pip` | Indicates picture-in-picture mode status |
| `data-fullscreen` | Indicates fullscreen mode status |

### Controls.Group

Container for organizing media control elements within the root.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Child components |

---

## Gesture

### Overview

The Gesture component enables media actions based on user interactions. It attaches event listeners to the media provider and triggers specified actions when events occur.

### Import

```tsx
import { Gesture, type GestureProps } from "@vidstack/react";
```

### Basic Usage

```tsx
<Gesture event="pointerup" action="toggle:paused" />
<Gesture event="dblpointerup" action="toggle:fullscreen" />
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Child content |
| `action` | `GestureAction` | `undefined` | Action to trigger on gesture |
| `disabled` | `boolean` | `false` | Disable gesture handling |
| `event` | `GestureEventType` | `undefined` | DOM event type to listen for |

### Callbacks

| Callback | Type | Description |
|----------|------|-------------|
| `onTrigger` | `function` | Fires when gesture action is triggered |
| `onWillTrigger` | `function` | Fires before action triggers; can prevent with `preventDefault()` |

### Events

Event listeners attach to the media provider. Valid DOM event types include `touchstart`, `mouseleave`, and others. Prefix any event with `dbl` to require two successive activations within 200ms (e.g., `dblpointerup`).

### Cancellation

Prevent gesture actions by calling `event.preventDefault()` on the will-trigger event.

### TypeScript Types

```tsx
type GestureProps = HTMLAttributes & {
  asChild?: boolean;
  children?: ReactNode;
  action?: GestureAction;
  disabled?: boolean;
  event?: GestureEventType;
  onTrigger?: (event: Event) => void;
  onWillTrigger?: (event: Event) => void;
};

type GestureInstance = Ref<HTMLElement>;
```

---

## Icons

### Overview

A collection of icons designed by Vidstack to help with building audio and video player user interfaces.

### Installation

```bash
npm install media-icons
```

### Usage

Icons can be imported in a lazy-loading manner for optimal performance:

```javascript
import { PlayIcon } from 'media-icons';
```

Then use within components:

```jsx
<PlayIcon />
```

### Key Information

- **Icon Catalog**: A complete preview of all available icons is accessible in the media icons catalog at `/icons`
- **Framework Support**: Works within React and Default Theme contexts
- **Integration**: Icons integrate with Vidstack's player components for standard media controls

---

## Captions

### Overview

The Captions component renders and displays captions/subtitles within the Vidstack player. It adapts its presentation based on the player's view type: appearing as an overlay for video content and as a simple captions box for audio content.

### Key Features

- Renders captions/subtitles dynamically
- Adapts display based on view type (video vs. audio)
- Powered by the media-captions library for enhanced rendering
- Automatically falls back to native captions on platforms where custom captions cannot be displayed (e.g., iOS Safari)

### Import

```typescript
import { Captions, type CaptionsProps } from "@vidstack/react";
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render using child element pattern |
| `children` | `ReactNode` | `null` | Child content |
| `exampleText` | `string` | `'Captions look like this.'` | Preview text for captions display |
| `textDir` | `string` | `'ltr'` | Text direction (left-to-right or right-to-left) |

### Ref Type

`Ref<CaptionsInstance>`

### Styling

The component supports CSS part styling for customization:
- Cues
- Voices
- Timed cues (past/future states)
- Regions

### Integration Notes

- Text track loading and management should be configured through the player's core loading capabilities
- The media-captions library handles rendering; see its repository for technical details
- Native captions provide insufficient customization, which is why this custom solution exists

---

## Thumbnail

### Overview

The Thumbnail component is used to load and display thumbnail images. For complete guidance on preparation, see the loading thumbnails core concepts guide.

### Import

```tsx
import { Thumbnail } from "@vidstack/react";
```

### Basic Usage

```tsx
<Thumbnail.Root src="thumbnails.vtt" time={10}>
  <Thumbnail.Img />
</Thumbnail.Root>
```

### Thumbnail.Root

The root component that loads and displays preview thumbnails at a specified time.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Child elements |
| `crossOrigin` | `mixed` | `null` | Cross-origin attribute |
| `src` | `ThumbnailSrc` | `null` | Thumbnail source (VTT file or array) |
| `time` | `number` | `0` | Time to display thumbnail for (seconds) |

#### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-loading` | Whether thumbnail image is loading |
| `data-error` | Whether an error occurred loading thumbnail |
| `data-hidden` | Whether thumbnail is not available or failed to load |

### Thumbnail.Img

The image element displaying the thumbnail preview.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `children` | `ReactNode` | `null` | Child content |
| `asChild` | `boolean` | `false` | Render as child element |

---

## Time

### Overview

The Time component displays media states as formatted time units, such as current playback time or total duration.

### Import

```typescript
import { Time, type TimeProps } from "@vidstack/react";
```

### Basic Usage

```tsx
<Time type="current" />
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |
| `children` | `ReactNode` | `null` | Child content |
| `hidden` | `boolean` | `false` | Hide the component |
| `padHours` | `boolean` | `null` | Pad hours with leading zeros |
| `padMinutes` | `boolean` | `null` | Pad minutes with leading zeros |
| `remainder` | `boolean` | `false` | Display remaining time instead |
| `showHours` | `boolean` | `false` | Display hours in output |
| `toggle` | `boolean` | `false` | Enable toggle behavior |
| `type` | `string` | `'current'` | Time type (`current`, `duration`, `bufferedAmount`, etc.) |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-type` | Reflects the active time type setting |
| `data-remainder` | Indicates whether remaining time display is active |

### Ref

`Ref<HTMLElement>` - Direct element reference access. Additionally accepts standard `HTMLAttributes`.

---

## Track

### Overview

The Track component is used to add text tracks to a media player. It creates a new `TextTrack` object and adds it to the player. For comprehensive information on managing text tracks, refer to the loading text tracks guide.

### Import

```tsx
import { Track, type TrackProps } from "@vidstack/react";
```

### Basic Usage

```jsx
<MediaPlayer>
  <MediaProvider>
    <Track
      src="english.vtt"
      kind="subtitles"
      label="English"
      lang="en-US"
      default
    />
  </MediaProvider>
</MediaPlayer>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `id` | `string` | `undefined` | Identifier for the track |
| `src` | `string` | `undefined` | Source URL for the track file |
| `content` | `string` | `undefined` | Inline track content |
| `type` | `string` | `'vtt'` | File format type |
| `encoding` | `string` | `'utf-8'` | Character encoding |
| `default` | `boolean` | `false` | Whether track is default |
| `kind` | `TextTrackKind` | `undefined` | Track category (`subtitles`, `captions`, `descriptions`, `chapters`, `metadata`) |
| `label` | `string` | `undefined` | User-facing track name |
| `language` | `string` | `undefined` | Language identifier |
| `lang` | `string` | `undefined` | Alternative language attribute |
| `key` | `string` | `undefined` | React key for list rendering |

---

## Title

### Overview

The Title component loads and displays the current media title within a video player interface.

### Import

```typescript
import { Title, type TitleProps } from "@vidstack/react";
```

### Basic Usage

```jsx
<Title />
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Render as child element |

### Ref

`Ref<HTMLElement>` for accessing the underlying DOM element.

### Functionality

This component automatically reflects title information from the loaded media source. The title is set via the `title` prop on the `MediaPlayer` component.

---

## Chapter Title

### Overview

The Chapter Title component displays the current chapter title from text tracks in a video player. It monitors the current playback position and updates the displayed title when the active chapter changes.

### Import

```typescript
import { ChapterTitle, type ChapterTitleProps } from "@vidstack/react";
```

### Basic Usage

```tsx
<ChapterTitle />
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `defaultText` | `string` | `undefined` | Text displayed when no chapter is active |
| `asChild` | `boolean` | `false` | Render as child element instead of wrapper |
| `ref` | `Ref<HTMLElement>` | - | Reference to underlying HTML element |

### Functionality

This component automatically loads and displays chapter titles based on text tracks provided to the player. Chapters are defined via a text track with `kind="chapters"` added to the player.

---

## Buffering Indicator

### Overview

The Buffering Indicator component displays a loading indicator during media buffering operations. It is part of the Display components section of Vidstack Player.

### Usage

The buffering indicator is typically used within the player layout to provide visual feedback when media is buffering. It can be customized via CSS styling.

### Basic Example

```tsx
import { BufferingIndicator } from "@vidstack/react";

<MediaPlayer>
  <MediaProvider />
  <BufferingIndicator />
</MediaPlayer>
```

### Styling

The component can be styled using CSS. Common patterns include:
- Using `data-buffering` data attribute on the player for conditional display
- Applying CSS animations for loading spinners
- Positioning the indicator centrally over the video

### Integration with Layouts

When using the Default Layout or Plyr Layout, a buffering indicator is automatically included. Manual usage is only needed for custom player layouts.

### Data Attributes

The parent `MediaPlayer` component provides the `data-buffering` attribute which indicates when the player is in a buffering state, and can be used to show/hide the buffering indicator via CSS selectors.
