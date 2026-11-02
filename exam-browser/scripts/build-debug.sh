#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TOOL_ROOT="${EXAM_BROWSER_TOOL_ROOT:-/home/ilhamzp/.local/share/codex-android}"

export JAVA_HOME="$TOOL_ROOT/jdk-17"
export ANDROID_SDK_ROOT="$TOOL_ROOT/sdk"
export PATH="$JAVA_HOME/bin:$ANDROID_SDK_ROOT/platform-tools:$PATH"

cd "$PROJECT_ROOT"
exec ./gradlew assembleDebug "$@"
