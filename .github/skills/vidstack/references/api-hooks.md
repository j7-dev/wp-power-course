# VidStack Player - API Hooks

Reference documentation for all React hooks provided by the `vidstack` player library.
Source: https://vidstack.io/docs/player/api/hooks/

---

## useState

This hook is used to subscribe to specific state on a component instance.

> Refer to each component page to see what state properties are available.

### Signature

```typescript
function useState<T extends AnyRecord, R extends keyof T>(
  ctor: { state: State<T> },
  prop: R,
  ref: React.RefObject<Component<any, T, any, any> | null>,
): T[R]
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `ctor` | `{ state: State<T> }` | The component constructor containing the state definition |
| `prop` | `R extends keyof T` | The specific state property to subscribe to |
| `ref` | `React.RefObject<Component<any, T, any, any> \| null>` | A ref to the component instance |

### Return Type

`T[R]` - The current value of the specified state property.

### Source

```typescript
import * as React from 'react';
import type { AnyRecord, Component, State } from 'maverick.js';
import { useSignal, useSignalRecord } from 'maverick.js/react';

export function useState<T extends AnyRecord, R extends keyof T>(
  ctor: { state: State<T> },
  prop: R,
  ref: React.RefObject<Component<any, T, any, any> | null>,
): T[R] {
  const initialValue = React.useMemo(() => ctor.state.record[prop], [ctor, prop]);
  return useSignal(ref.current ? ref.current.$state[prop] : initialValue);
}
```

---

## useStore

This hook is used to subscribe to multiple states on a component instance.

> Refer to each component page to see what state properties are available.

### Signature

```typescript
function useStore<T extends AnyRecord>(
  ctor: { state: State<T> },
  ref: React.RefObject<Component<any, T, any, any> | null>,
): T
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `ctor` | `{ state: State<T> }` | The component constructor containing the state definition |
| `ref` | `React.RefObject<Component<any, T, any, any> \| null>` | A ref to the component instance |

### Return Type

`T` - An object containing all state properties, reactively updated.

### Source

```typescript
const storesCache = new Map<any, any>();

export function useStore<T extends AnyRecord>(
  ctor: { state: State<T> },
  ref: React.RefObject<Component<any, T, any, any> | null>,
): T {
  const initialStore = React.useMemo<any>(() => {
    let store = storesCache.get(ctor);

    // Share the same initial store proxy across constructors.
    if (!store) {
      store = new Proxy(ctor.state.record, {
        get: (_, prop: any) => () => ctor.state.record[prop],
      });
      storesCache.set(ctor, store);
    }

    return store;
  }, [ctor]);

  return useSignalRecord(ref.current ? ref.current.$state : initialStore);
}
```

### Notes

- Uses a cache (`storesCache`) to share the same initial store proxy across constructors for performance.
- `useStore` subscribes to all state properties at once, unlike `useState` which subscribes to a single property.

---

## useMediaPlayer

Returns the nearest parent player component.

### Signature

```typescript
function useMediaPlayer(): MediaPlayerInstance | null
```

### Parameters

None.

### Return Type

`MediaPlayerInstance | null` - The nearest parent `MediaPlayer` instance, or `null` if none is found.

### Usage

```typescript
import { useMediaPlayer } from '@vidstack/react';

function MyComponent() {
  const player = useMediaPlayer();
  // Access player methods and properties
}
```

### Source

```typescript
import type { MediaPlayerInstance } from '../components/primitives/instances';
import { useMediaContext } from './use-media-context';

export function useMediaPlayer(): MediaPlayerInstance | null {
  const context = useMediaContext();

  if (__DEV__ && !context) {
    throw Error(
      '[vidstack] no media context was found - was this called outside of `<MediaPlayer>`?',
    );
  }

  return context?.player || null;
}
```

### Notes

- Must be called inside a child component of `<MediaPlayer>`.
- In development mode, throws an error if called outside of `<MediaPlayer>`.

---

## useMediaProvider

Returns the current parent media provider.

### Signature

```typescript
function useMediaProvider(): MediaProviderAdapter | null
```

### Parameters

None.

### Return Type

`MediaProviderAdapter | null` - The current media provider adapter, or `null` if none is active.

### Source

```typescript
import * as React from 'react';
import { effect } from 'maverick.js';
import { type MediaProviderAdapter } from 'vidstack';
import { useMediaContext } from './use-media-context';

export function useMediaProvider(): MediaProviderAdapter | null {
  const [provider, setProvider] = React.useState<MediaProviderAdapter | null>(null),
    context = useMediaContext();

  if (__DEV__ && !context) {
    throw Error(
      '[vidstack] no media context was found - was this called outside of `<MediaPlayer>`?',
    );
  }

  React.useEffect(() => {
    if (!context) return;
    return effect(() => {
      setProvider(context.$provider());
    });
  }, []);

  return provider;
}
```

### Notes

- Must be called inside a child component of `<MediaPlayer>`.
- Reactively updates when the provider changes (e.g., switching from HLS to native video).
- Returns `null` initially until a provider is loaded.

---

## useMediaRemote

A media remote provides a simple facade for dispatching media requests to the nearest media player.

### Signature

```typescript
function useMediaRemote(
  target?: EventTarget | null | React.RefObject<EventTarget | null>,
): MediaRemoteControl
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `target` | `EventTarget \| null \| React.RefObject<EventTarget \| null>` | (Optional) The DOM event target to dispatch request events from. Defaults to the player if no target is provided. |

### Return Type

`MediaRemoteControl` - A remote control instance for dispatching media requests.

### Source

```typescript
import * as React from 'react';
import { MediaRemoteControl } from 'vidstack';
import { MediaPlayerInstance } from '../components/primitives/instances';
import { useMediaContext } from './use-media-context';

export function useMediaRemote(
  target?: EventTarget | null | React.RefObject<EventTarget | null>,
): MediaRemoteControl {
  const media = useMediaContext(),
    remote = React.useRef<MediaRemoteControl>(null!);

  if (!remote.current) {
    remote.current = new MediaRemoteControl();
  }

  React.useEffect(() => {
    const ref = target && 'current' in target ? target.current : target,
      isPlayerRef = ref instanceof MediaPlayerInstance,
      player = isPlayerRef ? ref : media?.player;

    remote.current!.setPlayer(player ?? null);
    remote.current!.setTarget(ref ?? null);
  }, [media, target && 'current' in target ? target.current : target]);

  return remote.current;
}
```

### Usage

```typescript
import { useMediaRemote } from '@vidstack/react';

function PlayButton() {
  const remote = useMediaRemote();

  return (
    <button onClick={() => remote.togglePaused()}>
      Play/Pause
    </button>
  );
}
```

### Notes

- See the [`MediaRemoteControl`](https://vidstack.io/docs/player/api/classes/media-remote-control) API documentation for all available methods.
- The remote control persists across re-renders via `useRef`.
- Supports both raw `EventTarget` and React refs as the target parameter.

---

## useMediaState

This hook is used to subscribe to a specific media state.

### Signature

```typescript
function useMediaState<T extends keyof MediaState>(
  prop: T,
  ref?: React.RefObject<MediaPlayerInstance | null>,
): MediaState[T]
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `prop` | `T extends keyof MediaState` | The specific media state property to subscribe to (e.g., `'paused'`, `'currentTime'`, `'duration'`) |
| `ref` | `React.RefObject<MediaPlayerInstance \| null>` | (Optional) Required when calling outside of `<MediaPlayer>` component |

### Return Type

`MediaState[T]` - The current value of the specified media state property.

### Usage

```typescript
import { useMediaState } from '@vidstack/react';

// Inside <MediaPlayer>
function TimeDisplay() {
  const currentTime = useMediaState('currentTime');
  const duration = useMediaState('duration');
  const paused = useMediaState('paused');

  return <span>{currentTime} / {duration}</span>;
}

// Outside <MediaPlayer> with ref
function ExternalControls() {
  const playerRef = React.useRef<MediaPlayerInstance>(null);
  const paused = useMediaState('paused', playerRef);

  return <MediaPlayer ref={playerRef}>...</MediaPlayer>;
}
```

### Source

```typescript
import * as React from 'react';
import { useSignal, useSignalRecord, useStateContext } from 'maverick.js/react';
import { mediaState, type MediaState } from 'vidstack';
import { MediaPlayerInstance } from '../components/primitives/instances';

const mediaStateRecord = MediaPlayerInstance.state.record,
  initialMediaStore = Object.keys(mediaStateRecord).reduce(
    (store, prop) => ({
      ...store,
      [prop]() {
        return mediaStateRecord[prop];
      },
    }),
    {},
  );

export function useMediaState<T extends keyof MediaState>(
  prop: T,
  ref?: React.RefObject<MediaPlayerInstance | null>,
): MediaState[T] {
  const $state = useStateContext(mediaState);

  if (__DEV__ && !$state && !ref) {
    console.warn(
      `[vidstack] \`useMediaState\` requires \`RefObject<MediaPlayerInstance>\` argument if called` +
        ' outside the `<MediaPlayer>` component',
    );
  }

  return useSignal((ref?.current?.$state || $state || initialMediaStore)[prop]);
}
```

### Notes

- Subscribes to a **single** state property for fine-grained reactivity.
- When called outside `<MediaPlayer>`, a ref to the player instance is required.
- In development mode, a warning is logged if called without context and without a ref.

---

## useMediaStore

This hook is used to subscribe to the current media state on the nearest parent player. Subscribes to **all** media state properties at once.

### Signature

```typescript
function useMediaStore(
  ref?: React.RefObject<MediaPlayerInstance | null>,
): Readonly<MediaState>
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `ref` | `React.RefObject<MediaPlayerInstance \| null>` | (Optional) Required when calling outside of `<MediaPlayer>` component |

### Return Type

`Readonly<MediaState>` - A readonly object containing all media state properties, reactively updated.

### Usage

```typescript
import { useMediaStore } from '@vidstack/react';

// Inside <MediaPlayer>
function PlayerInfo() {
  const { paused, currentTime, duration, buffered } = useMediaStore();
  return <div>...</div>;
}

// Outside <MediaPlayer> with ref
function ExternalInfo() {
  const playerRef = React.useRef<MediaPlayerInstance>(null);
  const { paused, currentTime } = useMediaStore(playerRef);
  return <MediaPlayer ref={playerRef}>...</MediaPlayer>;
}
```

### Source

```typescript
export function useMediaStore(
  ref?: React.RefObject<MediaPlayerInstance | null>,
): Readonly<MediaState> {
  const $state = useStateContext(mediaState);

  if (__DEV__ && !$state && !ref) {
    console.warn(
      `[vidstack] \`useMediaStore\` requires \`RefObject<MediaPlayerInstance>\` argument if called` +
        ' outside the `<MediaPlayer>` component',
    );
  }

  return useSignalRecord(ref?.current ? ref.current.$state : $state || initialMediaStore);
}
```

### Notes

- Subscribes to **all** state properties at once. Use `useMediaState` for single-property subscriptions when fine-grained reactivity is needed.
- When called outside `<MediaPlayer>`, a ref to the player instance is required.

---

## useSliderState

This hook is used to subscribe to a specific slider state.

### Signature

```typescript
function useSliderState<T extends keyof SliderState>(
  prop: T,
  ref?: React.RefObject<SliderInstance | VolumeSliderInstance | TimeSliderInstance | null>,
): SliderState[T]
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `prop` | `T extends keyof SliderState` | The slider state property to subscribe to |
| `ref` | `React.RefObject<SliderInstance \| VolumeSliderInstance \| TimeSliderInstance \| null>` | (Optional) Required when calling outside of a slider component |

### Return Type

`SliderState[T]` - The current value of the specified slider state property.

### Usage

```typescript
import { useSliderState } from '@vidstack/react';

// Inside a slider component
function SliderValue() {
  const value = useSliderState('value');
  const dragging = useSliderState('dragging');
  return <span>{value}</span>;
}

// Outside a slider component with ref
function ExternalSliderInfo() {
  const sliderRef = React.useRef<SliderInstance>(null);
  const value = useSliderState('value', sliderRef);
  return <Slider ref={sliderRef}>...</Slider>;
}
```

### Source

```typescript
import * as React from 'react';
import { useSignal, useSignalRecord, useStateContext } from 'maverick.js/react';
import { sliderState, type SliderState } from 'vidstack';
import {
  SliderInstance,
  type TimeSliderInstance,
  type VolumeSliderInstance,
} from '../components/primitives/instances';

const sliderStateRecord = SliderInstance.state.record,
  initialSliderStore = Object.keys(sliderStateRecord).reduce(
    (store, prop) => ({
      ...store,
      [prop]() {
        return sliderStateRecord[prop];
      },
    }),
    {},
  );

export function useSliderState<T extends keyof SliderState>(
  prop: T,
  ref?: React.RefObject<SliderInstance | VolumeSliderInstance | TimeSliderInstance | null>,
): SliderState[T] {
  const $state = useStateContext(sliderState);

  if (__DEV__ && !$state && !ref) {
    console.warn(
      `[vidstack] \`useSliderState\` requires \`RefObject<SliderInstance>\` argument if called` +
        ' outside of a slider component',
    );
  }

  return useSignal((ref?.current?.$state || $state || initialSliderStore)[prop]);
}
```

### Notes

- Works inside any slider component (`Slider`, `VolumeSlider`, `TimeSlider`).
- When called outside a slider, a ref to the slider instance is required.

---

## useSliderStore

This hook is used to subscribe to the current slider state on the given or nearest slider component. Subscribes to **all** slider state properties at once.

### Signature

```typescript
function useSliderStore(
  ref?: React.RefObject<SliderInstance | VolumeSliderInstance | TimeSliderInstance | null>,
): Readonly<SliderState>
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `ref` | `React.RefObject<SliderInstance \| VolumeSliderInstance \| TimeSliderInstance \| null>` | (Optional) Required when calling outside of a slider component |

### Return Type

`Readonly<SliderState>` - A readonly object containing all slider state properties, reactively updated.

### Usage

```typescript
import { useSliderStore } from '@vidstack/react';

// Inside a slider component
function SliderInfo() {
  const { value, pointerValue, dragging } = useSliderStore();
  return <div>...</div>;
}

// Outside a slider component with ref
function ExternalSliderInfo() {
  const sliderRef = React.useRef<SliderInstance>(null);
  const { value, dragging } = useSliderStore(sliderRef);
  return <Slider ref={sliderRef}>...</Slider>;
}
```

### Source

```typescript
export function useSliderStore(
  ref?: React.RefObject<SliderInstance | VolumeSliderInstance | TimeSliderInstance | null>,
): Readonly<SliderState> {
  const $state = useStateContext(sliderState);

  if (__DEV__ && !$state && !ref) {
    console.warn(
      `[vidstack] \`useSliderStore\` requires \`RefObject<SliderInstance>\` argument if called` +
        ' outside of a slider component',
    );
  }

  return useSignalRecord(ref?.current ? ref.current.$state : $state || initialSliderStore);
}
```

### Notes

- Subscribes to **all** slider state properties. Use `useSliderState` for single-property subscriptions.
- Compatible with `SliderInstance`, `VolumeSliderInstance`, and `TimeSliderInstance` refs.

---

## useSliderPreview

This hook is used to create a floating panel above a custom slider.

### Signature

```typescript
function useSliderPreview(options?: UseSliderPreview): {
  previewRootRef: React.Dispatch<React.SetStateAction<HTMLElement | null>>;
  previewRef: React.Dispatch<React.SetStateAction<HTMLElement | null>>;
  previewValue: number;
  isPreviewVisible: boolean;
}
```

### Parameters (Options)

```typescript
interface UseSliderPreview {
  /**
   * Whether the preview should be clamped to the start and end of the slider root.
   * If `true` the preview won't be placed outside the root bounds.
   */
  clamp?: boolean;
  /**
   * The distance in pixels between the preview and the slider root.
   * You can also set the CSS variable `--media-slider-preview-offset` to adjust this offset.
   */
  offset?: number;
  /**
   * The orientation of the slider.
   */
  orientation?: SliderOrientation; // 'horizontal' | 'vertical'
}
```

**Defaults:** `clamp = false`, `offset = 0`, `orientation = 'horizontal'`

### Return Type

| Property | Type | Description |
|----------|------|-------------|
| `previewRootRef` | `React.Dispatch<SetStateAction<HTMLElement \| null>>` | Ref setter for the slider root element |
| `previewRef` | `React.Dispatch<SetStateAction<HTMLElement \| null>>` | Ref setter for the preview element |
| `previewValue` | `number` | The current pointer value (0-100) |
| `isPreviewVisible` | `boolean` | Whether the preview is currently visible |

### Usage

```typescript
import { useSliderPreview } from '@vidstack/react';

function CustomSlider() {
  const { previewRootRef, previewRef, previewValue, isPreviewVisible } = useSliderPreview({
    clamp: true,
    offset: 8,
    orientation: 'horizontal',
  });

  return (
    <div ref={previewRootRef}>
      {isPreviewVisible && (
        <div ref={previewRef}>
          {previewValue.toFixed(0)}%
        </div>
      )}
    </div>
  );
}
```

### Source

```typescript
export function useSliderPreview({
  clamp = false,
  offset = 0,
  orientation = 'horizontal',
}: UseSliderPreview = {}) {
  const [rootRef, setRootRef] = React.useState<HTMLElement | null>(null),
    [previewRef, setPreviewRef] = React.useState<HTMLElement | null>(null),
    [pointerValue, setPointerValue] = React.useState(0),
    [isVisible, setIsVisible] = React.useState(false);

  React.useEffect(() => {
    if (!rootRef) return;

    const dragging = signal(false);

    function updatePointerValue(event: PointerEvent) {
      if (!rootRef) return;
      setPointerValue(getPointerValue(rootRef, event, orientation));
    }

    return effect(() => {
      if (!dragging()) {
        new EventsController(rootRef)
          .add('pointerenter', () => {
            setIsVisible(true);
            previewRef?.setAttribute('data-visible', '');
          })
          .add('pointerdown', (event) => {
            dragging.set(true);
            updatePointerValue(event);
          })
          .add('pointerleave', () => {
            setIsVisible(false);
            previewRef?.removeAttribute('data-visible');
          })
          .add('pointermove', updatePointerValue);
      }

      previewRef?.setAttribute('data-dragging', '');

      new EventsController(document)
        .add('pointerup', (event) => {
          dragging.set(false);
          previewRef?.removeAttribute('data-dragging');
          updatePointerValue(event);
        })
        .add('pointermove', updatePointerValue)
        .add('touchmove', (e) => e.preventDefault(), { passive: false });
    });
  }, [rootRef]);

  React.useEffect(() => {
    if (previewRef) {
      previewRef.style.setProperty('--slider-pointer', pointerValue + '%');
    }
  }, [previewRef, pointerValue]);

  React.useEffect(() => {
    if (!previewRef) return;

    const update = () => {
      updateSliderPreviewPlacement(previewRef, { offset, clamp, orientation });
    };

    update();
    const resize = new ResizeObserver(update);
    resize.observe(previewRef);
    return () => resize.disconnect();
  }, [previewRef, clamp, offset, orientation]);

  return {
    previewRootRef: setRootRef,
    previewRef: setPreviewRef,
    previewValue: pointerValue,
    isPreviewVisible: isVisible,
  };
}
```

### Notes

- Sets `data-visible` and `data-dragging` attributes on the preview element for CSS styling.
- Sets `--slider-pointer` CSS variable on the preview element with the pointer percentage value.
- Uses `ResizeObserver` to recompute preview placement on resize.
- Handles both horizontal and vertical slider orientations.

---

## useChapterTitle

Returns the current chapter title text.

### Signature

```typescript
function useChapterTitle(): string
```

### Parameters

None.

### Return Type

`string` - The text of the currently active chapter cue, or an empty string if no chapter is active.

### Usage

```typescript
import { useChapterTitle } from '@vidstack/react';

function ChapterDisplay() {
  const title = useChapterTitle();
  return title ? <span>{title}</span> : null;
}
```

### Source

```typescript
import { useActiveTextCues } from './use-active-text-cues';
import { useActiveTextTrack } from './use-active-text-track';

export function useChapterTitle(): string {
  const $track = useActiveTextTrack('chapters'),
    $cues = useActiveTextCues($track);

  return $cues[0]?.text || '';
}
```

### Notes

- Internally uses `useActiveTextTrack('chapters')` and `useActiveTextCues()`.
- Requires chapter text tracks to be loaded on the player.
- Returns the text of the first active cue.

---

## useThumbnails

Fetches, parses, and resolves thumbnail images from a WebVTT file, JSON file, or Object.

It's safe to call this hook in multiple places with the same `src` argument as work is de-duped and cached internally.

### Signature

```typescript
function useThumbnails(
  src: ThumbnailSrc,
  crossOrigin?: MediaCrossOrigin | null,
): ThumbnailImage[]
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `src` | `ThumbnailSrc` | The thumbnail source (WebVTT URL, JSON URL, or object) |
| `crossOrigin` | `MediaCrossOrigin \| null` | (Optional) Cross-origin setting for thumbnail requests. Defaults to `null`. |

### Return Type

`ThumbnailImage[]` - An array of resolved thumbnail images, each containing:
- `startTime` - Start time of the thumbnail
- `endTime` - End time of the thumbnail
- `url` - The thumbnail image URL
- Coordinates and dimensions for sprite sheets

### Usage

```typescript
import { useThumbnails, useActiveThumbnail } from '@vidstack/react';

function ThumbnailPreview({ time }: { time: number }) {
  const thumbnails = useThumbnails('https://example.com/thumbnails.vtt');
  const activeThumbnail = useActiveThumbnail(thumbnails, time);

  if (!activeThumbnail) return null;

  return (
    <img
      src={activeThumbnail.url}
      style={{
        width: activeThumbnail.width,
        height: activeThumbnail.height,
      }}
    />
  );
}
```

### useActiveThumbnail

A companion hook for retrieving the currently active thumbnail at a specific time.

```typescript
function useActiveThumbnail(
  thumbnails: ThumbnailImage[],
  time: number,
): ThumbnailImage | null
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `thumbnails` | `ThumbnailImage[]` | The resolved thumbnail images from `useThumbnails` |
| `time` | `number` | The current time to determine which thumbnail is active |

Returns the active `ThumbnailImage` or `null`.

### Source

```typescript
import * as React from 'react';
import { useReactScope, useSignal } from 'maverick.js/react';
import {
  ThumbnailsLoader,
  type MediaCrossOrigin,
  type ThumbnailImage,
  type ThumbnailSrc,
} from 'vidstack';
import { createSignal, useScoped } from './use-signals';

export function useThumbnails(
  src: ThumbnailSrc,
  crossOrigin: MediaCrossOrigin | null = null,
): ThumbnailImage[] {
  const scope = useReactScope(),
    $src = createSignal(src),
    $crossOrigin = createSignal(crossOrigin),
    loader = useScoped(() => ThumbnailsLoader.create($src, $crossOrigin));

  if (__DEV__ && !scope) {
    console.warn(
      `[vidstack] \`useThumbnails\` must be called inside a child component of \`<MediaPlayer>\``,
    );
  }

  React.useEffect(() => {
    $src.set(src);
  }, [src]);

  React.useEffect(() => {
    $crossOrigin.set(crossOrigin);
  }, [crossOrigin]);

  return useSignal(loader.$images);
}

export function useActiveThumbnail(
  thumbnails: ThumbnailImage[],
  time: number,
): ThumbnailImage | null {
  return React.useMemo(() => {
    let activeIndex = -1;
    for (let i = thumbnails.length - 1; i >= 0; i--) {
      const image = thumbnails[i];
      if (time >= image.startTime && (!image.endTime || time < image.endTime)) {
        activeIndex = i;
        break;
      }
    }
    return thumbnails[activeIndex] || null;
  }, [thumbnails, time]);
}
```

### Notes

- Must be called inside a child component of `<MediaPlayer>`.
- Work is de-duped and cached across multiple calls with the same `src`.
- See the [Loading Thumbnails guide](https://vidstack.io/docs/player/core-concepts/loading#thumbnails) for more details.

---

## createTextTrack

Creates a new `TextTrack` object and adds it to the player. The track is automatically removed on component unmount.

### Signature

```typescript
function createTextTrack(init: TextTrackInit): TextTrack
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `init` | `TextTrackInit` | Configuration object for the text track (kind, label, language, src, etc.) |

### Return Type

`TextTrack` - The created text track instance.

### Usage

```typescript
import { createTextTrack } from '@vidstack/react';

function SubtitleLoader() {
  const track = createTextTrack({
    kind: 'subtitles',
    label: 'English',
    language: 'en',
    src: '/subs/english.vtt',
  });

  return null;
}
```

### Source

```typescript
import * as React from 'react';
import { TextTrack, type TextTrackInit } from 'vidstack';
import { useMediaContext } from './use-media-context';

export function createTextTrack(init: TextTrackInit) {
  const media = useMediaContext(),
    track = React.useMemo(() => new TextTrack(init), Object.values(init));

  React.useEffect(() => {
    media.textTracks.add(track);
    return () => void media.textTracks.remove(track);
  }, [track]);

  return track;
}
```

### Notes

- Despite the name, this is a React hook (uses `useMemo` and `useEffect`) and must follow the Rules of Hooks.
- The track is memoized based on the values of the `init` object.
- Automatically adds the track to `media.textTracks` on mount and removes it on unmount.

---

## useTextCues

This hook is used to observe cues on the given text track. Returns all cues (not just active ones).

### Signature

```typescript
function useTextCues(track: TextTrack | null): VTTCue[]
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `track` | `TextTrack \| null` | The text track to observe cues on |

### Return Type

`VTTCue[]` - An array of all VTT cues on the track.

### Usage

```typescript
import { useTextCues, useActiveTextTrack } from '@vidstack/react';

function CueList() {
  const track = useActiveTextTrack('subtitles');
  const cues = useTextCues(track);

  return (
    <ul>
      {cues.map((cue, i) => (
        <li key={i}>{cue.text}</li>
      ))}
    </ul>
  );
}
```

### Source

```typescript
import * as React from 'react';
import { EventsController } from 'maverick.js/std';
import type { VTTCue } from 'media-captions';
import type { TextTrack } from 'vidstack';

export function useTextCues(track: TextTrack | null): VTTCue[] {
  const [cues, setCues] = React.useState<VTTCue[]>([]);

  React.useEffect(() => {
    if (!track) return;

    function onCuesChange() {
      if (track) setCues([...track.cues]);
    }

    const events = new EventsController(track)
      .add('add-cue', onCuesChange)
      .add('remove-cue', onCuesChange);

    onCuesChange();

    return () => {
      setCues([]);
      events.abort();
    };
  }, [track]);

  return cues;
}
```

### Notes

- Listens to `add-cue` and `remove-cue` events on the track.
- Returns a spread copy of the cues array (immutable).
- Resets to an empty array when the track changes or on cleanup.

---

## useActiveTextCues

This hook is used to subscribe to the active text cues for a given text track. Only returns cues that are currently active based on the playback time.

### Signature

```typescript
function useActiveTextCues(track: TextTrack | null): VTTCue[]
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `track` | `TextTrack \| null` | The text track to observe active cues on |

### Return Type

`VTTCue[]` - An array of currently active VTT cues.

### Usage

```typescript
import { useActiveTextCues, useActiveTextTrack } from '@vidstack/react';

function ActiveSubtitles() {
  const track = useActiveTextTrack('subtitles');
  const activeCues = useActiveTextCues(track);

  return (
    <div>
      {activeCues.map((cue, i) => (
        <p key={i}>{cue.text}</p>
      ))}
    </div>
  );
}
```

### Source

```typescript
import * as React from 'react';
import { listenEvent } from 'maverick.js/std';
import type { VTTCue } from 'media-captions';
import type { TextTrack } from 'vidstack';

export function useActiveTextCues(track: TextTrack | null): VTTCue[] {
  const [activeCues, setActiveCues] = React.useState<VTTCue[]>([]);

  React.useEffect(() => {
    if (!track) {
      setActiveCues([]);
      return;
    }

    function onCuesChange() {
      if (track) setActiveCues(track.activeCues as VTTCue[]);
    }

    onCuesChange();
    return listenEvent(track, 'cue-change', onCuesChange);
  }, [track]);

  return activeCues;
}
```

### Notes

- Listens to the `cue-change` event on the track.
- Differs from `useTextCues` which returns all cues; this returns only currently active cues.
- Resets to an empty array when track is `null` or changes.

---

## useActiveTextTrack

This hook is used to retrieve the active text track of a specific kind(s).

### Signature

```typescript
function useActiveTextTrack(
  kind: TextTrackKind | TextTrackKind[],
): TextTrack | null
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `kind` | `TextTrackKind \| TextTrackKind[]` | The kind(s) of text track to watch. Values: `'subtitles'`, `'captions'`, `'descriptions'`, `'chapters'`, `'metadata'` |

### Return Type

`TextTrack | null` - The currently active text track of the specified kind, or `null`.

### Usage

```typescript
import { useActiveTextTrack } from '@vidstack/react';

function SubtitleInfo() {
  const track = useActiveTextTrack('subtitles');
  return track ? <span>Active: {track.label}</span> : <span>No subtitles</span>;
}

// Multiple kinds
function CaptionTrack() {
  const track = useActiveTextTrack(['subtitles', 'captions']);
  return track ? <span>{track.label}</span> : null;
}
```

### Source

```typescript
import * as React from 'react';
import { watchActiveTextTrack, type TextTrack } from 'vidstack';
import { useMediaContext } from './use-media-context';

export function useActiveTextTrack(kind: TextTrackKind | TextTrackKind[]): TextTrack | null {
  const media = useMediaContext(),
    [track, setTrack] = React.useState<TextTrack | null>(null);

  React.useEffect(() => {
    return watchActiveTextTrack(media.textTracks, kind, setTrack);
  }, [kind]);

  return track;
}
```

### Notes

- Uses `watchActiveTextTrack` from vidstack internally.
- Accepts a single kind or an array of kinds.
- Automatically cleans up the watcher on unmount.

---

## useAudioOptions

This hook is used to retrieve the current audio track options.

### Signature

```typescript
function useAudioOptions(): AudioOptions
```

### Parameters

None.

### Return Type

```typescript
type AudioOptions = AudioOption[] & {
  readonly disabled: boolean;
  readonly selectedTrack: AudioTrack | null;
  readonly selectedValue: string | undefined;
};

interface AudioOption {
  readonly track: AudioTrack;
  readonly label: string;
  readonly value: string;
  readonly selected: boolean;
  select(trigger?: Event): void;
}
```

### Usage

```typescript
import { useAudioOptions } from '@vidstack/react';

function AudioTrackMenu() {
  const options = useAudioOptions();

  if (options.disabled) return null;

  return (
    <ul>
      {options.map((option) => (
        <li key={option.value}>
          <button
            onClick={(e) => option.select(e.nativeEvent)}
            data-selected={option.selected || undefined}
          >
            {option.label}
          </button>
        </li>
      ))}
    </ul>
  );
}
```

### Source

```typescript
import * as React from 'react';
import { useSignal } from 'maverick.js/react';
import { type AudioTrack } from 'vidstack';
import { useMediaContext } from '../use-media-context';

export function useAudioOptions(): AudioOptions {
  const media = useMediaContext(),
    { audioTracks, audioTrack } = media.$state,
    $audioTracks = useSignal(audioTracks);

  useSignal(audioTrack);

  return React.useMemo(() => {
    const options = $audioTracks.map<AudioOption>((track) => ({
      track,
      label: track.label,
      value: getTrackValue(track),
      get selected() {
        return audioTrack() === track;
      },
      select(trigger) {
        const index = audioTracks().indexOf(track);
        if (index >= 0) media.remote.changeAudioTrack(index, trigger);
      },
    }));

    Object.defineProperty(options, 'disabled', {
      get() { return options.length <= 1; },
    });

    Object.defineProperty(options, 'selectedTrack', {
      get() { return audioTrack(); },
    });

    Object.defineProperty(options, 'selectedValue', {
      get() {
        const track = audioTrack();
        return track ? getTrackValue(track) : undefined;
      },
    });

    return options as AudioOptions;
  }, [$audioTracks]);
}

function getTrackValue(track: AudioTrack) {
  return track.label.toLowerCase();
}
```

### Notes

- `disabled` is `true` when there is 1 or fewer audio tracks.
- Each option's `value` is the track label in lowercase.
- Uses `media.remote.changeAudioTrack()` to change the active audio track.

---

## useAudioGainOptions

This hook is used to retrieve the current audio gain options.

### Signature

```typescript
function useAudioGainOptions(options?: UseAudioGainOptions): AudioGainOptions
```

### Parameters (Options)

```typescript
interface UseAudioGainOptions {
  /** Array of gain values or objects with label/gain. Defaults to DEFAULT_AUDIO_GAINS. */
  gains?: (number | { label: string; gain: number })[];
  /** Label for the disabled/off option (gain = 1). Defaults to 'disabled'. */
  disabledLabel?: string | null;
}
```

### Return Type

```typescript
type AudioGainOptions = AudioGainOption[] & {
  readonly disabled: boolean;
  readonly selectedValue: string | undefined;
};

interface AudioGainOption {
  readonly label: string;
  readonly value: string;
  readonly gain: number;
  readonly selected: boolean;
  select(trigger?: Event): void;
}
```

### Usage

```typescript
import { useAudioGainOptions } from '@vidstack/react';

function AudioGainMenu() {
  const options = useAudioGainOptions({
    gains: [1, 1.25, 1.5, 2],
    disabledLabel: 'Normal',
  });

  if (options.disabled) return null;

  return (
    <ul>
      {options.map((option) => (
        <li key={option.value}>
          <button
            onClick={(e) => option.select(e.nativeEvent)}
            data-selected={option.selected || undefined}
          >
            {option.label}
          </button>
        </li>
      ))}
    </ul>
  );
}
```

### Source

```typescript
export function useAudioGainOptions({
  gains = DEFAULT_AUDIO_GAINS,
  disabledLabel = 'disabled',
}: UseAudioGainOptions = {}): AudioGainOptions {
  const media = useMediaContext(),
    { audioGain, canSetAudioGain } = media.$state;

  useSignal(audioGain);
  useSignal(canSetAudioGain);

  return React.useMemo(() => {
    const options = gains.map<AudioGainOption>((opt) => {
      const label =
          typeof opt === 'number'
            ? opt === 1 && disabledLabel
              ? disabledLabel
              : opt * 100 + '%'
            : opt.label,
        gain = typeof opt === 'number' ? opt : opt.gain;
      return {
        label,
        value: gain.toString(),
        gain,
        get selected() {
          return audioGain() === gain;
        },
        select(trigger) {
          media.remote.changeAudioGain(gain, trigger);
        },
      };
    });

    Object.defineProperty(options, 'disabled', {
      get() { return !canSetAudioGain() || !options.length; },
    });

    Object.defineProperty(options, 'selectedValue', {
      get() { return audioGain()?.toString(); },
    });

    return options as AudioGainOptions;
  }, [gains]);
}
```

### Notes

- `disabled` is `true` when `canSetAudioGain` is `false` or there are no gain options.
- Gain of `1` represents normal audio (100%). The `disabledLabel` is used as its label.
- Numeric gains are displayed as percentages (e.g., `1.5` becomes `"150%"`).

---

## useCaptionOptions

This hook is used to retrieve the current caption/subtitle text track options.

### Signature

```typescript
function useCaptionOptions(options?: UseCaptionOptions): CaptionOptions
```

### Parameters (Options)

```typescript
interface UseCaptionOptions {
  /**
   * Whether an option should be included for turning off all captions.
   * A string can be provided to specify the label.
   * @default true
   */
  off?: boolean | string;
}
```

### Return Type

```typescript
type CaptionOptions = CaptionOption[] & {
  readonly disabled: boolean;
  readonly selectedTrack: TextTrack | null;
  readonly selectedValue: string;
};

interface CaptionOption {
  readonly track: TextTrack | null;
  readonly label: string;
  readonly value: string;
  readonly selected: boolean;
  select(trigger?: Event): void;
}
```

### Usage

```typescript
import { useCaptionOptions } from '@vidstack/react';

function CaptionMenu() {
  const options = useCaptionOptions({ off: 'None' });

  if (options.disabled) return null;

  return (
    <ul>
      {options.map((option) => (
        <li key={option.value}>
          <button
            onClick={(e) => option.select(e.nativeEvent)}
            data-selected={option.selected || undefined}
          >
            {option.label}
          </button>
        </li>
      ))}
    </ul>
  );
}
```

### Source

```typescript
export function useCaptionOptions({ off = true }: UseCaptionOptions = {}): CaptionOptions {
  const media = useMediaContext(),
    { textTracks, textTrack } = media.$state,
    $textTracks = useSignal(textTracks);

  useSignal(textTrack);

  return React.useMemo(() => {
    const captionTracks = $textTracks.filter(isTrackCaptionKind),
      options = captionTracks.map<CaptionOption>((track) => ({
        track,
        label: track.label,
        value: getTrackValue(track),
        get selected() {
          return textTrack() === track;
        },
        select(trigger) {
          const index = textTracks().indexOf(track);
          if (index >= 0) media.remote.changeTextTrackMode(index, 'showing', trigger);
        },
      }));

    if (off) {
      options.unshift({
        track: null,
        label: isString(off) ? off : 'Off',
        value: 'off',
        get selected() {
          return !textTrack();
        },
        select(trigger) {
          media.remote.toggleCaptions(trigger);
        },
      });
    }

    Object.defineProperty(options, 'disabled', {
      get() { return !captionTracks.length; },
    });

    Object.defineProperty(options, 'selectedTrack', {
      get() { return textTrack(); },
    });

    Object.defineProperty(options, 'selectedValue', {
      get() {
        const track = textTrack();
        return track ? getTrackValue(track) : 'off';
      },
    });

    return options as CaptionOptions;
  }, [$textTracks]);
}

function getTrackValue(track: TextTrack) {
  return track.id + ':' + track.kind + '-' + track.label.toLowerCase();
}
```

### Notes

- Filters text tracks to only caption/subtitle kinds using `isTrackCaptionKind`.
- When `off` is `true`, an "Off" option is prepended. When `off` is a string, it's used as the label.
- `disabled` is `true` when there are no caption tracks.
- Track values are formatted as `"id:kind-label"`.

---

## useChapterOptions

This hook is used to retrieve the current chapter options.

### Signature

```typescript
function useChapterOptions(): ChapterOptions
```

### Parameters

None.

### Return Type

```typescript
type ChapterOptions = ChapterOption[] & {
  readonly selectedValue: string | undefined;
};

interface ChapterOption {
  readonly cue: VTTCue;
  readonly label: string;
  readonly value: string;
  readonly selected: boolean;
  readonly startTimeText: string;
  readonly durationText: string;
  select(trigger?: Event): void;
  /** Sets a `--progress` CSS variable on the given element representing the played percentage. */
  setProgressVar(ref: HTMLElement | null): void;
}
```

### Usage

```typescript
import { useChapterOptions } from '@vidstack/react';

function ChapterMenu() {
  const options = useChapterOptions();

  return (
    <ul>
      {options.map((option) => (
        <li
          key={option.value}
          ref={(el) => option.setProgressVar(el)}
        >
          <button
            onClick={(e) => option.select(e.nativeEvent)}
            data-selected={option.selected || undefined}
          >
            <span>{option.label}</span>
            <span>{option.startTimeText}</span>
            <span>{option.durationText}</span>
          </button>
        </li>
      ))}
    </ul>
  );
}
```

### Source

```typescript
export function useChapterOptions(): ChapterOptions {
  const media = useMediaContext(),
    track = useActiveTextTrack('chapters'),
    cues = useTextCues(track),
    $startTime = useSignal(media.$state.seekableStart),
    $endTime = useSignal(media.$state.seekableEnd);

  useActiveTextCues(track);

  return React.useMemo(() => {
    const options = track
      ? cues
          .filter((cue) => cue.startTime <= $endTime && cue.endTime >= $startTime)
          .map<ChapterOption>((cue, i) => {
            let currentRef: HTMLElement | null = null,
              stopProgressEffect: StopEffect | undefined;
            return {
              cue,
              label: cue.text,
              value: i.toString(),
              startTimeText: formatTime(Math.max(0, cue.startTime - $startTime)),
              durationText: formatSpokenTime(
                Math.min($endTime, cue.endTime) - Math.max($startTime, cue.startTime),
              ),
              get selected() {
                return cue === track.activeCues[0];
              },
              setProgressVar(ref) {
                if (!ref || cue !== track.activeCues[0]) {
                  stopProgressEffect?.();
                  stopProgressEffect = undefined;
                  ref?.style.setProperty('--progress', '0%');
                  currentRef = null;
                  return;
                }
                if (currentRef === ref) return;
                currentRef = ref;
                stopProgressEffect?.();
                stopProgressEffect = effect(() => {
                  const { realCurrentTime } = media.$state,
                    time = realCurrentTime(),
                    cueStartTime = Math.max($startTime, cue.startTime),
                    duration = Math.min($endTime, cue.endTime) - cueStartTime,
                    progress = (Math.max(0, time - cueStartTime) / duration) * 100;
                  ref.style.setProperty('--progress', progress.toFixed(3) + '%');
                });
              },
              select(trigger) {
                media.remote.seek(cue.startTime - $startTime, trigger);
              },
            };
          })
      : [];

    Object.defineProperty(options, 'selectedValue', {
      get() {
        const index = options.findIndex((option) => option.selected);
        return (index >= 0 ? index : 0).toString();
      },
    });

    return options as ChapterOptions;
  }, [cues, $startTime, $endTime]);
}
```

### Notes

- Filters cues to only those within the seekable range.
- `setProgressVar` sets a `--progress` CSS variable representing the played percentage of the chapter. Use this for progress bar styling.
- `startTimeText` is formatted with `formatTime()`.
- `durationText` is formatted with `formatSpokenTime()` for accessibility.
- `select()` seeks to the chapter's start time.

---

## usePlaybackRateOptions

This hook is used to retrieve the current playback rate options.

### Signature

```typescript
function usePlaybackRateOptions(options?: UsePlaybackRateOptions): PlaybackRateOptions
```

### Parameters (Options)

```typescript
interface UsePlaybackRateOptions {
  /** Array of rate values or objects with label/rate. Defaults to DEFAULT_PLAYBACK_RATES. */
  rates?: (number | { label: string; rate: number })[];
  /** Label for the normal speed (rate = 1). Defaults to 'Normal'. */
  normalLabel?: string | null;
}
```

### Return Type

```typescript
type PlaybackRateOptions = PlaybackRateOption[] & {
  readonly disabled: boolean;
  readonly selectedValue: string | undefined;
};

interface PlaybackRateOption {
  readonly label: string;
  readonly value: string;
  readonly rate: number;
  readonly selected: boolean;
  select(trigger?: Event): void;
}
```

### Usage

```typescript
import { usePlaybackRateOptions } from '@vidstack/react';

function PlaybackRateMenu() {
  const options = usePlaybackRateOptions({
    rates: [0.5, 0.75, 1, 1.25, 1.5, 2],
    normalLabel: 'Normal',
  });

  if (options.disabled) return null;

  return (
    <ul>
      {options.map((option) => (
        <li key={option.value}>
          <button
            onClick={(e) => option.select(e.nativeEvent)}
            data-selected={option.selected || undefined}
          >
            {option.label}
          </button>
        </li>
      ))}
    </ul>
  );
}
```

### Source

```typescript
export function usePlaybackRateOptions({
  rates = DEFAULT_PLAYBACK_RATES,
  normalLabel = 'Normal',
}: UsePlaybackRateOptions = {}): PlaybackRateOptions {
  const media = useMediaContext(),
    { playbackRate, canSetPlaybackRate } = media.$state;

  useSignal(playbackRate);
  useSignal(canSetPlaybackRate);

  return React.useMemo(() => {
    const options = rates.map<PlaybackRateOption>((opt) => {
      const label =
          typeof opt === 'number'
            ? opt === 1 && normalLabel
              ? normalLabel
              : opt + 'x'
            : opt.label,
        rate = typeof opt === 'number' ? opt : opt.rate;
      return {
        label,
        value: rate.toString(),
        rate,
        get selected() {
          return playbackRate() === rate;
        },
        select(trigger) {
          media.remote.changePlaybackRate(rate, trigger);
        },
      };
    });

    Object.defineProperty(options, 'disabled', {
      get() { return !canSetPlaybackRate() || !options.length; },
    });

    Object.defineProperty(options, 'selectedValue', {
      get() { return playbackRate().toString(); },
    });

    return options as PlaybackRateOptions;
  }, [rates]);
}
```

### Notes

- `disabled` is `true` when `canSetPlaybackRate` is `false` or there are no rate options.
- Rate of `1` uses the `normalLabel` (default: `"Normal"`). Other numeric rates are formatted as `"Nx"` (e.g., `"1.5x"`).
- Uses `media.remote.changePlaybackRate()` to change the active playback rate.

---

## useVideoQualityOptions

This hook is used to retrieve the current video playback quality options.

### Signature

```typescript
function useVideoQualityOptions(options?: UseVideoQualityOptions): VideoQualityOptions
```

### Parameters (Options)

```typescript
interface UseVideoQualityOptions {
  /**
   * Whether an auto option should be included. A string can be provided to specify the label.
   * @default true
   */
  auto?: boolean | string;
  /**
   * Specifies how the options should be sorted. The sorting algorithm looks at both
   * the quality resolution and bitrate.
   *
   * - Ascending: 480p, 720p, 720p (higher bitrate), 1080p
   * - Descending: 1080p, 720p (higher bitrate), 720p, 480p
   *
   * @default 'descending'
   */
  sort?: 'ascending' | 'descending';
}
```

### Return Type

```typescript
type VideoQualityOptions = VideoQualityOption[] & {
  readonly disabled: boolean;
  readonly selectedQuality: VideoQuality | null;
  readonly selectedValue: string;
};

interface VideoQualityOption {
  readonly quality: VideoQuality | null;
  readonly label: string;
  readonly value: string;
  readonly selected: boolean;
  readonly autoSelected: boolean;
  readonly bitrateText: string | null;
  select(trigger?: Event): void;
}
```

### Usage

```typescript
import { useVideoQualityOptions } from '@vidstack/react';

function QualityMenu() {
  const options = useVideoQualityOptions({
    auto: 'Auto',
    sort: 'descending',
  });

  if (options.disabled) return null;

  return (
    <ul>
      {options.map((option) => (
        <li key={option.value}>
          <button
            onClick={(e) => option.select(e.nativeEvent)}
            data-selected={option.selected || undefined}
          >
            {option.label}
            {option.bitrateText && <span>({option.bitrateText})</span>}
          </button>
        </li>
      ))}
    </ul>
  );
}
```

### Source

```typescript
export function useVideoQualityOptions({
  auto = true,
  sort = 'descending',
}: UseVideoQualityOptions = {}): VideoQualityOptions {
  const media = useMediaContext(),
    { qualities, quality, autoQuality, canSetQuality } = media.$state,
    $qualities = useSignal(qualities);

  useSignal(quality);
  useSignal(autoQuality);
  useSignal(canSetQuality);

  return React.useMemo(() => {
    const sortedQualities = sortVideoQualities($qualities, sort === 'descending'),
      options = sortedQualities.map<VideoQualityOption>((q) => {
        return {
          quality: q,
          label: q.height + 'p',
          value: getQualityValue(q),
          bitrateText:
            q.bitrate && q.bitrate > 0 ? `${(q.bitrate / 1000000).toFixed(2)} Mbps` : null,
          get selected() {
            return q === quality();
          },
          get autoSelected() {
            return autoQuality();
          },
          select(trigger) {
            const index = qualities().indexOf(q);
            if (index >= 0) media.remote.changeQuality(index, trigger);
          },
        };
      });

    if (auto) {
      options.unshift({
        quality: null,
        label: isString(auto) ? auto : 'Auto',
        value: 'auto',
        bitrateText: null,
        get selected() {
          return autoQuality();
        },
        get autoSelected() {
          return autoQuality();
        },
        select(trigger) {
          media.remote.requestAutoQuality(trigger);
        },
      });
    }

    Object.defineProperty(options, 'disabled', {
      get() { return !canSetQuality() || $qualities.length <= 1; },
    });

    Object.defineProperty(options, 'selectedQuality', {
      get() { return quality(); },
    });

    Object.defineProperty(options, 'selectedValue', {
      get() {
        const $quality = quality();
        return !autoQuality() && $quality ? getQualityValue($quality) : 'auto';
      },
    });

    return options as VideoQualityOptions;
  }, [$qualities, sort]);
}

function getQualityValue(quality: VideoQuality) {
  return quality.height + '_' + quality.bitrate;
}
```

### Notes

- `disabled` is `true` when `canSetQuality` is `false` or there is 1 or fewer quality options.
- When `auto` is `true`, an "Auto" option is prepended. When `auto` is a string, it's used as the label.
- Quality labels are formatted as `"Np"` (e.g., `"1080p"`).
- Bitrate is formatted as Mbps (e.g., `"2.50 Mbps"`) when available.
- Quality values are formatted as `"height_bitrate"` for uniqueness.
- Qualities are sorted by resolution and bitrate according to the `sort` parameter.
