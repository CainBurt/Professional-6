const path = require("path");

const in_path = "./src/js/src/";
const out_path = "./dist/js/";

module.exports = {
  entry: `${in_path}index.js`,

  output: {
    path: path.resolve(__dirname, out_path),
    filename: "bundle.js",
  },

};
