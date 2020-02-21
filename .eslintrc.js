module.exports = {
  root: true,
  env: {
    node: true,
  },
  rules: {
    'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'indent': ['error', 'tab'],
    'no-tabs': ['off'],
    'no-param-reassign': 0,
  },
};
