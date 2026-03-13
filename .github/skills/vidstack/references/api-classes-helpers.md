# VidStack Player - API Classes & Helpers

## MediaRemoteControl

A simple facade for dispatching media requests to the nearest media player element.

The `MediaRemoteControl` class provides a centralized interface for controlling media playback and player state through event dispatching. Events bubble up from a target element to the player element.

**Source:** `maverick.js` / `vidstack` core
**Docs:** https://www.vidstack.io/docs/player/core-concepts/state-management#updating

### Constructor

```typescript
constructor(logger?: Logger)
```

### Private Properties

```typescript
#target: EventTarget | null = null;
#player: MediaPlayer | null = null;
#prevTrackIndex = -1;
#logger?: Logger;
```

### Methods

#### setTarget

Set the target from which to dispatch media request events. The events should bubble up from this target to the player element.

```typescript
setTarget(target: EventTarget | null): void
```

**Example:**

```ts
const button = document.querySelector('button');
remote.setTarget(button);
```

#### getPlayer

Returns the current player element. This method will attempt to find the player by searching up from either the given `target` or default target set via `remote.setTarget`.

```typescript
getPlayer(target?: EventTarget | null): MediaPlayer | null
```

Internally dispatches a `find-media-player` event that bubbles and is composed, with a detail callback that sets the internal player reference.

**Example:**

```ts
const player = remote.getPlayer();
```

#### setPlayer

Set the current player element so the remote can support toggle methods such as `togglePaused` as they rely on the current media state.

```typescript
setPlayer(player: MediaPlayer | null): void
```

#### startLoading

Dispatch a request to start the media loading process. This will only work if the media player has been initialized with a custom loading strategy `load="custom"`.

```typescript
startLoading(trigger?: Event): void
```

Dispatches: `media-start-loading`

#### startLoadingPoster

Dispatch a request to start the poster loading process. This will only work if the media player has been initialized with a custom poster loading strategy `posterLoad="custom"`.

```typescript
startLoadingPoster(trigger?: Event): void
```

Dispatches: `media-poster-start-loading`

#### play

Dispatch a request to begin/resume media playback.

```typescript
play(trigger?: Event): void
```

Dispatches: `media-play-request`

#### pause

Dispatch a request to pause media playback.

```typescript
pause(trigger?: Event): void
```

Dispatches: `media-pause-request`

#### mute

Dispatch a request to set the media volume to mute (0).

```typescript
mute(trigger?: Event): void
```

Dispatches: `media-mute-request`

#### unmute

Dispatch a request to unmute the media volume and set it back to its previous state.

```typescript
unmute(trigger?: Event): void
```

Dispatches: `media-unmute-request`

#### enterFullscreen

Dispatch a request to enter fullscreen.

```typescript
enterFullscreen(target?: MediaFullscreenRequestTarget, trigger?: Event): void
```

Dispatches: `media-enter-fullscreen-request`

#### exitFullscreen

Dispatch a request to exit fullscreen.

```typescript
exitFullscreen(target?: MediaFullscreenRequestTarget, trigger?: Event): void
```

Dispatches: `media-exit-fullscreen-request`

#### lockScreenOrientation

Dispatch a request to lock the screen orientation.

```typescript
lockScreenOrientation(lockType: ScreenOrientationLockType, trigger?: Event): void
```

Dispatches: `media-orientation-lock-request`

#### unlockScreenOrientation

Dispatch a request to unlock the screen orientation.

```typescript
unlockScreenOrientation(trigger?: Event): void
```

Dispatches: `media-orientation-unlock-request`

#### enterPictureInPicture

Dispatch a request to enter picture-in-picture mode.

```typescript
enterPictureInPicture(trigger?: Event): void
```

Dispatches: `media-enter-pip-request`

#### exitPictureInPicture

Dispatch a request to exit picture-in-picture mode.

```typescript
exitPictureInPicture(trigger?: Event): void
```

Dispatches: `media-exit-pip-request`

#### seeking

Notify the media player that a seeking process is happening and to seek to the given `time`.

```typescript
seeking(time: number, trigger?: Event): void
```

Dispatches: `media-seeking-request`

#### seek

Notify the media player that a seeking operation has completed and to seek to the given `time`. This is generally called after a series of `remote.seeking()` calls.

```typescript
seek(time: number, trigger?: Event): void
```

Dispatches: `media-seek-request`

#### seekToLiveEdge

Dispatch a request to seek to the live edge of streaming content.

```typescript
seekToLiveEdge(trigger?: Event): void
```

Dispatches: `media-live-edge-request`

#### changeDuration

Dispatch a request to update the length of the media in seconds.

```typescript
changeDuration(duration: number, trigger?: Event): void
```

Dispatches: `media-duration-change-request`

**Example:**

```ts
remote.changeDuration(100); // 100 seconds
```

#### changeClipStart

Dispatch a request to update the clip start time. This is the time at which media playback should start at.

```typescript
changeClipStart(startTime: number, trigger?: Event): void
```

Dispatches: `media-clip-start-change-request`

**Example:**

```ts
remote.changeClipStart(100); // start at 100 seconds
```

#### changeClipEnd

Dispatch a request to update the clip end time. This is the time at which media playback should end at.

```typescript
changeClipEnd(endTime: number, trigger?: Event): void
```

Dispatches: `media-clip-end-change-request`

**Example:**

```ts
remote.changeClipEnd(100); // end at 100 seconds
```

#### changeVolume

Dispatch a request to update the media volume to the given `volume` level which is a value between 0 and 1. The value is automatically clamped between 0 and 1.

```typescript
changeVolume(volume: number, trigger?: Event): void
```

Dispatches: `media-volume-change-request`

**Example:**

```ts
remote.changeVolume(0);    // 0%
remote.changeVolume(0.05); // 5%
remote.changeVolume(0.5);  // 50%
remote.changeVolume(0.75); // 75%
remote.changeVolume(1);    // 100%
```

#### changeAudioTrack

Dispatch a request to change the current audio track.

```typescript
changeAudioTrack(index: number, trigger?: Event): void
```

Dispatches: `media-audio-track-change-request`

**Example:**

```ts
remote.changeAudioTrack(1); // track at index 1
```

#### changeQuality

Dispatch a request to change the video quality. The special value `-1` represents auto quality selection.

```typescript
changeQuality(index: number, trigger?: Event): void
```

Dispatches: `media-quality-change-request`

**Example:**

```ts
remote.changeQuality(-1); // auto
remote.changeQuality(1);  // quality at index 1
```

#### requestAutoQuality

Request auto quality selection. Shorthand for `changeQuality(-1)`.

```typescript
requestAutoQuality(trigger?: Event): void
```

#### changeTextTrackMode

Dispatch a request to change the mode of the text track at the given index.

```typescript
changeTextTrackMode(index: number, mode: TextTrackMode, trigger?: Event): void
```

Dispatches: `media-text-track-change-request` with detail `{ index, mode }`

**Example:**

```ts
remote.changeTextTrackMode(1, 'showing'); // track at index 1
```

#### changePlaybackRate

Dispatch a request to change the media playback rate.

```typescript
changePlaybackRate(rate: number, trigger?: Event): void
```

Dispatches: `media-rate-change-request`

**Example:**

```ts
remote.changePlaybackRate(0.5);  // Half the normal speed
remote.changePlaybackRate(1);    // Normal speed
remote.changePlaybackRate(1.5);  // 50% faster than normal
remote.changePlaybackRate(2);    // Double the normal speed
```

#### changeAudioGain

Dispatch a request to change the media audio gain.

```typescript
changeAudioGain(gain: number, trigger?: Event): void
```

Dispatches: `media-audio-gain-change-request`

**Example:**

```ts
remote.changeAudioGain(1);   // Disable audio gain
remote.changeAudioGain(1.5); // 50% louder
remote.changeAudioGain(2);   // 100% louder
```

#### pauseControls

Dispatch a request to pause controls idle tracking. Pausing tracking will result in the controls being visible until `remote.resumeControls()` is called. This method is generally used when building custom controls and you'd like to prevent the UI from disappearing.

```typescript
pauseControls(trigger?: Event): void
```

Dispatches: `media-pause-controls-request`

**Example:**

```ts
// Prevent controls hiding while menu is being interacted with.
function onSettingsOpen() {
  remote.pauseControls();
}

function onSettingsClose() {
  remote.resumeControls();
}
```

#### resumeControls

Dispatch a request to resume idle tracking on controls.

```typescript
resumeControls(trigger?: Event): void
```

Dispatches: `media-resume-controls-request`

#### togglePaused

Dispatch a request to toggle the media playback state. Checks player state internally -- if paused, calls `play()`; if playing, calls `pause()`.

```typescript
togglePaused(trigger?: Event): void
```

#### toggleControls

Dispatch a request to toggle the controls visibility. Checks `player.controls.showing` internally.

```typescript
toggleControls(trigger?: Event): void
```

#### toggleMuted

Dispatch a request to toggle the media muted state. Checks `player.state.muted` internally.

```typescript
toggleMuted(trigger?: Event): void
```

#### toggleFullscreen

Dispatch a request to toggle the media fullscreen state. Checks `player.state.fullscreen` internally.

```typescript
toggleFullscreen(target?: MediaFullscreenRequestTarget, trigger?: Event): void
```

#### togglePictureInPicture

Dispatch a request to toggle the media picture-in-picture mode. Checks `player.state.pictureInPicture` internally.

```typescript
togglePictureInPicture(trigger?: Event): void
```

#### showCaptions

Show captions. Attempts to find a suitable caption track in this order:
1. Previously active caption track (`#prevTrackIndex`)
2. Track marked as `default`
3. First caption track found

```typescript
showCaptions(trigger?: Event): void
```

#### disableCaptions

Turn captions off. Stores the current track index for later restoration via `showCaptions()`.

```typescript
disableCaptions(trigger?: Event): void
```

#### toggleCaptions

Dispatch a request to toggle the current captions mode. If a text track is currently active, disables captions; otherwise shows captions.

```typescript
toggleCaptions(trigger?: Event): void
```

#### requestAirPlay

Dispatch a request to connect to AirPlay.

```typescript
requestAirPlay(trigger?: Event): void
```

Dispatches: `media-airplay-request`

#### requestGoogleCast

Dispatch a request to connect to Google Cast.

```typescript
requestGoogleCast(trigger?: Event): void
```

Dispatches: `media-google-cast-request`

#### userPrefersLoopChange

Dispatch a request to change the user's loop preference.

```typescript
userPrefersLoopChange(prefersLoop: boolean, trigger?: Event): void
```

Dispatches: `media-user-loop-change-request`

### Internal Dispatch Mechanism

All methods internally use `#dispatchRequest` which:

1. Creates a `DOMEvent` with `bubbles: true`, `composed: true`, `cancelable: true`
2. Determines the correct target (trigger target, set target, or player element)
3. If a player exists and the request type is not `media-play-request` (or the player can already load), it enqueues the request via `player.canPlayQueue`
4. Otherwise dispatches the event directly on the target

```typescript
#dispatchRequest<EventType extends keyof MediaRequestEvents>(
  type: EventType,
  trigger?: Event,
  detail?: MediaRequestEvents[EventType]['detail'],
): void
```

---

## Event Triggers

The `EventTriggers` class provides utilities for managing event trigger chains. Every event dispatched by the player (as a `DOMEvent`) includes a `triggers` property containing an `EventTriggers` instance, which maintains a history of events responsible for triggering subsequent events.

For example, a user click triggers `media-play-request`, which triggers `play` -- establishing a trigger chain.

**Source:** `maverick.js/std` (`DOMEvent` and `EventTriggers`)

### DOMEvent Class

All player events extend `DOMEvent`, which automatically provides trigger chain support.

```typescript
interface DOMEventInit<Detail = unknown> extends EventInit {
  readonly detail: Detail;
  readonly trigger?: Event;
}

class DOMEvent<Detail = unknown> extends Event {
  /** The event detail. */
  readonly detail: Detail;

  /** The event trigger chain. */
  readonly triggers: EventTriggers;

  /** The preceding event that was responsible for this event being fired. */
  get trigger(): Event | undefined;

  /** The origin event that lead to this event being fired. */
  get originEvent(): Event | undefined;

  /**
   * Whether the origin event was triggered by the user.
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Event/isTrusted
   */
  get isOriginTrusted(): boolean;

  constructor(
    type: string,
    ...init: Detail extends void | undefined | never
      ? [init?: Partial<DOMEventInit<Detail>>]
      : [init: DOMEventInit<Detail>]
  );
}
```

### EventTriggers Class

```typescript
class EventTriggers implements Iterable<Event> {
  readonly chain: Event[];

  /** The preceding event that was responsible for this event being fired (first in chain). */
  get source(): Event | undefined;

  /** The origin event that started the chain (last in chain). */
  get origin(): Event | undefined;
}
```

### Properties

#### chain

```typescript
readonly chain: Event[]
```

The array of events in the trigger chain.

#### source

```typescript
get source(): Event | undefined
```

Returns the first event in the chain -- the direct preceding event that was responsible for triggering this event.

#### origin

```typescript
get origin(): Event | undefined
```

Returns the last event in the chain -- the original event that started the entire trigger chain.

### Methods

#### add

Appends the event to the end of the chain. If the event is itself a `DOMEvent`, its own trigger chain is also appended.

```typescript
add(event: Event): void
```

**Implementation detail:** When a `DOMEvent` is added, the method spreads its triggers into the chain as well:

```ts
add(event: Event): void {
  this.chain.push(event);
  if (isDOMEvent(event)) {
    this.chain.push(...event.triggers);
  }
}
```

#### remove

Removes the event from the chain and returns it (if found).

```typescript
remove(event: Event): Event | undefined
```

#### has

Returns whether the chain contains the given `event`.

```typescript
has(event: Event): boolean
```

#### hasType

Returns whether the chain contains an event with the given type.

```typescript
hasType(type: string): boolean
```

#### findType

Returns the first event with the given `type` found in the chain.

```typescript
findType(type: string): Event | undefined
```

#### walk

Walks the event chain and invokes the given `callback` for each trigger event. If the callback returns a non-nullish value, walking terminates early and returns `[event, value]`.

```typescript
walk<T>(
  callback: (event: Event) => NonNullable<T> | void,
): [event: Event, value: NonNullable<T>] | undefined
```

#### [Symbol.iterator]

The class implements `Iterable<Event>`, allowing use with `for...of`:

```typescript
[Symbol.iterator](): Iterator<Event>
```

### Helper Functions

#### isDOMEvent

Whether the given `event` is a `DOMEvent` class instance.

```typescript
function isDOMEvent(event?: Event | null): event is DOMEvent<unknown>
```

#### isPointerEvent

Whether the event is a pointer event (type starts with "pointer").

```typescript
function isPointerEvent(event: Event | undefined): event is PointerEvent
```

#### isTouchEvent

Whether the event is a touch event (type starts with "touch").

```typescript
function isTouchEvent(event: Event | undefined): event is TouchEvent
```

#### isMouseEvent

Whether the event is a mouse event (type starts with "click" or "mouse").

```typescript
function isMouseEvent(event: Event | undefined): event is MouseEvent
```

#### isKeyboardEvent

Whether the event is a keyboard event (type starts with "key").

```typescript
function isKeyboardEvent(event: Event | undefined): event is KeyboardEvent
```

#### wasEnterKeyPressed

Whether the keyboard event was the Enter key.

```typescript
function wasEnterKeyPressed(event: Event | undefined): boolean
```

#### wasEscapeKeyPressed

Whether the keyboard event was the Escape key.

```typescript
function wasEscapeKeyPressed(event: Event | undefined): boolean
```

#### isKeyboardClick

Whether the keyboard event was Enter or Space (a "keyboard click").

```typescript
function isKeyboardClick(event: Event | undefined): boolean
```

### Deprecated Helper Functions

These functions are deprecated in favor of the `EventTriggers` instance methods on `event.triggers`:

#### getOriginEvent (deprecated)

Use `event.originEvent` instead.

```typescript
function getOriginEvent(event: DOMEvent): Event | undefined
```

#### walkTriggerEventChain (deprecated)

Use `event.triggers.walk(callback)` instead.

```typescript
function walkTriggerEventChain<T>(
  event: Event,
  callback: (event: Event) => NonNullable<T> | void,
): [event: Event, value: NonNullable<T>] | undefined
```

#### findTriggerEvent (deprecated)

Use `event.triggers.findType('')` instead.

```typescript
function findTriggerEvent(event: Event, type: string): Event | undefined
```

#### hasTriggerEvent (deprecated)

Use `event.triggers.hasType('')` instead.

```typescript
function hasTriggerEvent(event: Event, type: string): boolean
```

#### appendTriggerEvent (deprecated)

Use `event.triggers.add(event)` instead.

```typescript
function appendTriggerEvent(event: DOMEvent, trigger?: Event): void
```

### Related Utilities

#### listenEvent

Adds an event listener for the given `type` and returns a function which can be invoked to remove the event listener. The listener is removed if the current scope is disposed. This function is safe to use on the server (noop).

```typescript
function listenEvent<
  Target extends EventTarget,
  Events = InferEvents<Target>,
  Type extends keyof Events = keyof Events,
>(
  target: Target,
  type: Type & string,
  handler: TargetedEventHandler<Target, Events[Type] extends Event ? Events[Type] : Event>,
  options?: AddEventListenerOptions | boolean,
): Dispose
```

#### EventsController

A controller class for managing multiple event listeners with automatic cleanup via `AbortController`.

```typescript
class EventsController<Target extends EventTarget, Events = InferEvents<Target>> {
  get signal(): AbortSignal;

  constructor(target: Target);

  add<Type extends keyof Events>(
    type: Type,
    handler: TargetedEventHandler<Target, Events[Type] extends Event ? Events[Type] : Event>,
    options?: AddEventListenerOptions,
  ): this;

  remove<Type extends keyof Events>(
    type: Type,
    handler: TargetedEventHandler<Target, Events[Type] extends Event ? Events[Type] : Event>,
  ): this;

  abort(reason?: string): void;
}
```

#### anySignal

Returns an `AbortSignal` that will abort when any of the given signals are aborted.

```typescript
function anySignal(...signals: AbortSignal[]): AbortSignal
```
