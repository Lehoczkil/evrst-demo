export function NoiseFilter() {
  return (
    <div
      style={{
        position: 'fixed',
        width: '100%',
        height: '100vh',
        zIndex: 1000,
        pointerEvents: 'none',
      }}
    >
      <svg id='noice' width='100%' height='100%'>
        <filter id='noise-filter'>
          <feTurbulence
            type='fractalNoise'
            baseFrequency='1'
            numOctaves='4'
            stitchTiles='stitch'
          ></feTurbulence>
          <feColorMatrix type='saturate' values='0'></feColorMatrix>
          <feComponentTransfer>
            <feFuncR type='linear' slope='0.46'></feFuncR>
            <feFuncG type='linear' slope='0.46'></feFuncG>
            <feFuncB type='linear' slope='0.46'></feFuncB>
            <feFuncA type='linear' slope='0.08'></feFuncA>
          </feComponentTransfer>
          <feComponentTransfer>
            <feFuncR type='linear' slope='1.47' intercept='-0.23' />
            <feFuncG type='linear' slope='1.47' intercept='-0.23' />
            <feFuncB type='linear' slope='1.47' intercept='-0.23' />
          </feComponentTransfer>
        </filter>
        <rect width='100%' height='100%' filter='url(#noise-filter)'></rect>
      </svg>
    </div>
  );
}
