import fusion, { ts, parallel, wait } from '@windwalker-io/fusion';

export async function js() {
  // Watch start
  fusion.watch('src/**/*.ts');
  // Watch end

  return wait(
    ts('src/**/*.ts', 'dist/', { tsconfig: 'tsconfig.json' })
  );
}

export default parallel(js);
