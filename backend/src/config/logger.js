import dotenv from 'dotenv';

dotenv.config();

const LOG_LEVELS = {
  error: 0,
  warn: 1,
  info: 2,
  debug: 3
};

const currentLogLevel = LOG_LEVELS[process.env.LOG_LEVEL || 'info'];

const logger = {
  error: (message, error = null) => {
    if (currentLogLevel >= LOG_LEVELS.error) {
      console.error(`[ERROR] ${new Date().toISOString()} - ${message}`, error || '');
    }
  },
  warn: (message) => {
    if (currentLogLevel >= LOG_LEVELS.warn) {
      console.warn(`[WARN] ${new Date().toISOString()} - ${message}`);
    }
  },
  info: (message) => {
    if (currentLogLevel >= LOG_LEVELS.info) {
      console.log(`[INFO] ${new Date().toISOString()} - ${message}`);
    }
  },
  debug: (message, data = null) => {
    if (currentLogLevel >= LOG_LEVELS.debug) {
      console.log(`[DEBUG] ${new Date().toISOString()} - ${message}`, data || '');
    }
  }
};

export default logger;
