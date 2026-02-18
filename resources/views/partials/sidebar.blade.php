@php
  // $drawerMenu viene del View::composer en AppServiceProvider
  $menu = $drawerMenu ?? collect();
@endphp

<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="logo">
      <img src="{{ asset('assets/logo.png') }}" alt="Logo"/>
    </div>
    <div class="title">
      <b>Laravel CMS</b>
      <span>Lotificaciones</span>
    </div>
  </div>

  <nav class="nav">

    @foreach($menu as $parent)
      @php
        $children = $parent->children_nodes ?? collect();
        $hasChildren = $children && count($children) > 0;

        $parentKey = 'm'.$parent->id;
      @endphp

      @if(!$hasChildren)
        <a href="{{ $parent->ruta ?: '#' }}" class="nav-link {{ request()->is(ltrim($parent->ruta,'/').'*') ? 'active' : '' }}">
          <div class="left">
            <i class="{{ $parent->icono ?: 'fa-solid fa-circle' }}"></i>
            <div class="text">
              <div class="t">{{ $parent->nombre }}</div>
              <div class="s">Módulo</div>
            </div>
          </div>
          <span class="chip">Go</span>
        </a>
      @else
        <button class="nav-parent {{ request()->is('*') ? '' : '' }}" type="button" data-parent="{{ $parentKey }}">
          <div class="left">
            <i class="{{ $parent->icono ?: 'fa-solid fa-layer-group' }}"></i>
            <div class="text">
              <div class="t">{{ $parent->nombre }}</div>
              <div class="s">Sección</div>
            </div>
          </div>
          <div class="caret"><i class="fa-solid fa-chevron-down"></i></div>
        </button>

        <div class="submenu" data-submenu="{{ $parentKey }}">
          @foreach($children as $child)
            <a href="{{ $child->ruta ?: '#' }}"
               class="nav-link {{ request()->is(ltrim($child->ruta,'/').'*') ? 'active' : '' }}"
               data-parent="{{ $parentKey }}">
              <div class="left">
                <i class="{{ $child->icono ?: 'fa-regular fa-file-lines' }}"></i>
                <div class="text">
                  <div class="t">{{ $child->nombre }}</div>
                  <div class="s">CRUD</div>
                </div>
              </div>
            </a>
          @endforeach
        </div>
      @endif
    @endforeach

  </nav>
</aside>
