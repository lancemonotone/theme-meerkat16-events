// Return array of configurations. Comment out unneeded.
module.exports = function () {
  return exportModules( [
    js,
    css
  ] );
};

// Config for JS files
const js = {
  entry: {
    // Add other entries as needed
    'app': `${__dirname}/src/js/app.js`
  },
  output: {
    path: `${__dirname}/build/js`,
    // Need '.js' for webpack to generate correct file
    filename: '[name].js'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        use: { loader: 'babel-loader' }, exclude: /node_modules/
      },
    ]
  }
};

// Config for SCSS files
const css = {
  entry: {
    // Add other entries as needed
    'style': `${__dirname}/src/scss/style.scss`,
    'admin': `${__dirname}/src/scss/admin.scss`
  },
  output: {
    path: `${__dirname}/build/css`,
    // Unlike with js, no '.css' or webpack will complain of duplicate files
    filename: '[name]'
  },
  module: {
    rules: [
      {
        test: /\.scss$/,
        exclude: /node_modules/,
        // module chain executes from last to first
        use: [
          {
            loader: 'file-loader',
            options: { name: '[name].css', outputPath: '../css/' }
          },
          { loader: 'extract-loader' },
          // Don't process @import statements. Importing fonts from URLs will throw error.
          { loader: 'css-loader', options: { import: false } },
          { loader: 'resolve-url-loader', options: { removeCR: true } },
          { loader: 'sass-loader', options: { sourceMap: true } }
        ]
      },
    ]
  }
};

/**
 * Merge filetype configs with shared config and return them as an array of objects.
 * @param objs
 * @return {Array}
 */
const exportModules = ( objs ) => {
  const objArr = [];
  for ( let i = 0; i < objs.length; i++ ) {
    objArr.push( {
      ...config(),
      ...objs[i]
    } );
  }
  return objArr;
};

// Shared config options
const config = function () {
  return {
    mode: 'production',
    devtool: 'source-map',
    watchOptions: {
      ignored: /node_modules/
    },
    stats: {
      colors: true,
      hash: false,
      version: false,
      timings: false,
      assets: true,
      chunks: false,
      modules: false,
      reasons: false,
      children: false,
      source: false,
      errors: true,
      errorDetails: false,
      warnings: true,
      publicPath: false
    }
  }
};