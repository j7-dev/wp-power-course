# VidStack Player - Menu & Remotion Components

## Menu

The Menu component displays content or options in a floating panel. It supports nested menus for creating submenus and includes comprehensive keyboard navigation and accessibility features.

### Basic Structure

```tsx
<Menu.Root>
  <Menu.Button></Menu.Button>
  <Menu.Content placement="top end"></Menu.Content>
</Menu.Root>
```

### Root (`Menu.Root`)

The container managing the menu button and menu items. Accepts `HTMLAttributes` and a `Ref<MenuInstance>`.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | - | Menu contents |
| `showDelay` | `number` | `0` | Delay before showing menu |

**Callbacks:**

| Callback | Description |
|----------|-------------|
| `onOpen()` | Fires when menu opens |
| `onClose()` | Fires when menu closes |
| `onMediaPauseControlsRequest()` | Requests control pause |
| `onMediaResumeControlsRequest()` | Requests control resume |

**Instance Methods:**

| Method | Description |
|--------|-------------|
| `open()` | Opens the menu |
| `close()` | Closes the menu |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-root` | Identifies root menu |
| `data-submenu` | Indicates submenu status |
| `data-open` | Reflects open state |
| `data-keyboard` | Set when opened via keyboard |
| `data-disabled` | Reflects disabled state |

### Button (`Menu.Button`)

Controls menu opening/closing. Becomes a `menuitem` when nested in submenus.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | - | Button contents |
| `disabled` | `boolean` | `false` | Disables the button |

**Callbacks:**

| Callback | Description |
|----------|-------------|
| `onSelect()` | Fires on selection |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-root` | Root button indicator |
| `data-submenu` | Submenu button indicator |
| `data-open` | Open state |
| `data-focus` | Keyboard focus state |
| `data-hocus` | Keyboard focus or hover state |

### Items (`Menu.Items`)

Groups and displays settings or content in a floating panel.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | - | Items contents |
| `placement` | `MenuPlacement` | `null` | Panel positioning |
| `alignOffset` | `number` | `0` | Alignment offset |
| `offset` | `number` | `0` | Distance from trigger |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-root` | Root items indicator |
| `data-submenu` | Submenu items indicator |
| `data-open` | Visibility state |
| `data-keyboard` | Keyboard open indicator |
| `data-placement` | Placement setting |
| `data-focus` | Focus state |
| `data-hocus` | Focus or hover state |
| `data-transition` | Active resize transition |

### Item (`Menu.Item`)

Individual option or action within the menu.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | - | Item contents |
| `disabled` | `boolean` | `false` | Disables the item |

**Callbacks:**

| Callback | Description |
|----------|-------------|
| `onSelect()` | Fires on selection |

### Portal (`Menu.Portal`)

Portals menu items into specified containers.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | - | Portal contents |
| `container` | `string` | `null` | Target container selector |
| `disabled` | `mixed` | `false` | Portal activation |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-portal` | Portal active status |

### RadioGroup (`Menu.RadioGroup`)

Manages single-selection options where only one can be checked.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | - | Radio group contents |
| `value` | `string` | `''` | Currently selected value |

**Callbacks:**

| Callback | Description |
|----------|-------------|
| `onChange()` | Fires on value change |

**Instance Properties:**

| Property | Type | Description |
|----------|------|-------------|
| `values` | `string[]` | Available options |

### Radio (`Menu.Radio`)

Individual selectable option within a radio group.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | - | Radio contents |
| `value` | `string` | `''` | Option identifier |

**Callbacks:**

| Callback | Description |
|----------|-------------|
| `onSelect()` | Fires on selection |
| `onChange()` | Fires on state change |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-checked` | Selection state |
| `data-focus` | Keyboard focus |
| `data-hocus` | Focus or hover state |

### Height Transitions

The `--menu-height` CSS variable enables smooth height transitions. The `data-transition='height'` attribute appears during active transitions, allowing you to style animated resizing behavior.

### Nested Submenus

Create hierarchical menus by nesting menu structures:

```tsx
<Menu.Root>
  <Menu.Button>Settings</Menu.Button>
  <Menu.Content>
    <Menu.Item>Option 1</Menu.Item>
    <Menu.Root>
      <Menu.Button>Submenu</Menu.Button>
      <Menu.Content placement="right start">
        <Menu.Item>Nested Option</Menu.Item>
      </Menu.Content>
    </Menu.Root>
  </Menu.Content>
</Menu.Root>
```

### Accessibility

The component supports full keyboard navigation, screen reader announcements, and ARIA attributes for accessible menu interactions across assistive technologies.

---

## Radio Group

The Radio Group component displays a set of mutually exclusive options where only one selection can be active at a time. It is composed of a root container and individual radio items.

### Basic Usage

```tsx
import { RadioGroup } from "@vidstack/react";

<RadioGroup.Root>
  <RadioGroup.Item value="1080">1080p</RadioGroup.Item>
  <RadioGroup.Item value="720">720p</RadioGroup.Item>
</RadioGroup.Root>
```

### Root (`RadioGroup.Root`)

The container for radio options.

**Import:**

```typescript
import { RadioGroup } from "@vidstack/react";
const Component = RadioGroup.Root;
type Props = RadioGroup.RootProps;
```

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | `null` | Radio group contents |
| `value` | `string` | `''` | Currently selected value |

**Callbacks:**

| Callback | Description |
|----------|-------------|
| `onChange` | Fires on value change |

**Instance Reference:**

```typescript
import { RadioGroup, type RadioGroupInstance } from "@vidstack/react";
const ref = useRef<RadioGroupInstance>(null);
```

Instance property: `values` (`string[]`) - available options.

### Item (`RadioGroup.Item`)

Individual radio options within the group.

**Import:**

```typescript
const Component = RadioGroup.Item;
type Props = RadioGroup.ItemProps;
```

**Usage:**

```tsx
<RadioGroup.Item value="1080">1080p</RadioGroup.Item>
```

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Renders as child element |
| `children` | `ReactNode` | `null` | Item contents |
| `value` | `string` | `''` | Option identifier |

**Callbacks:**

| Callback | Description |
|----------|-------------|
| `onChange` | Fires on state change |
| `onSelect` | Fires on selection |

**Data Attributes:**

| Attribute | Description |
|-----------|-------------|
| `data-checked` | Indicates whether radio is selected |
| `data-focus` | Indicates keyboard focus state |
| `data-hocus` | Indicates keyboard focus or hover state |

---

## Remotion Poster

The RemotionPoster component renders a specified frame from a Remotion video, typically displayed before playback begins.

### Usage

```tsx
import { RemotionPoster, type RemotionPosterProps } from "@vidstack/react";

<MediaPlayer>
  <MediaProvider>
    <RemotionPoster frame={100} />
  </MediaProvider>
</MediaPlayer>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `frame` | `number` | `undefined` | Specifies which frame of the video to display |
| `renderLoading` | `RemotionLoadingRenderer` | `undefined` | Custom loading renderer component |
| `errorFallback` | `RemotionErrorRenderer` | `undefined` | Custom error fallback component |
| `asChild` | `boolean` | `false` | Render as child element |
| `ref` | `Ref<HTMLElement>` | - | React ref for DOM access |

### Callbacks

| Callback | Description |
|----------|-------------|
| `onError` | Handles rendering errors |

### Data Attributes

| Attribute | Description |
|-----------|-------------|
| `data-visible` | Indicates whether the poster should be displayed |

### Type Definition

The component accepts `RefAttributes` and exports `RemotionPosterProps` for TypeScript support.

---

## Remotion Thumbnail

The RemotionThumbnail component renders a specified frame from a Remotion video.

### Usage

```tsx
import { RemotionThumbnail, type RemotionThumbnailProps } from "@vidstack/react";

<RemotionThumbnail frame={100} />
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `frame` | `number` | `undefined` | Specifies which frame of the Remotion video to display |
| `renderLoading` | `RemotionLoadingRenderer` | `undefined` | Custom renderer displayed while the frame is loading |
| `errorFallback` | `RemotionErrorRenderer` | `undefined` | Custom renderer shown when an error occurs during rendering |
| `asChild` | `boolean` | `false` | Modifies component rendering behavior |

### Callbacks

| Callback | Description |
|----------|-------------|
| `onError` | Invoked when an error occurs during frame rendering |

### Component Reference

- **Ref**: `Ref<HTMLElement>` - Access to the underlying HTML element
- Supports standard React `RefAttributes`

---

## Remotion Slider Thumbnail

The RemotionSliderThumbnail component renders preview frames from Remotion videos that appear over the time slider during playback.

### Usage

The component is implemented within the `TimeSlider` structure:

```tsx
import { RemotionSliderThumbnail } from "@vidstack/react";

<TimeSlider.Root>
  <TimeSlider.Preview>
    <RemotionSliderThumbnail />
  </TimeSlider.Preview>
</TimeSlider.Root>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `asChild` | `boolean` | `false` | Determines whether the component renders as a child element |
| `renderLoading` | `RemotionLoadingRenderer` | `undefined` | Custom loader display function for handling loading states |
| `errorFallback` | `RemotionErrorRenderer` | `undefined` | Custom error display function when preview rendering fails |

### Callbacks

| Callback | Description |
|----------|-------------|
| `onError` | Triggers when an error occurs during thumbnail rendering |

### Component Reference

- **Ref**: `Ref<HTMLElement>` - Access to the underlying HTML element
- Supports standard React `RefAttributes`

### Related Components

- Remotion Thumbnail
- Time Slider
