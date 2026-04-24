/**!
 * markdown-it-mark
 *
 * Copyright (c) 2014-2015 Vitaly Puzrin, Alex Kocharin.
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */
'use strict'
const pipeMark = 0x7c /* | */

const isDefaultTerminator = (code) => {
  switch (code) {
    case 0x0a /* \n */:
    case 0x21 /* ! */:
    case 0x23 /* # */:
    case 0x24 /* $ */:
    case 0x25 /* % */:
    case 0x26 /* & */:
    case 0x2a /* * */:
    case 0x2b /* + */:
    case 0x2d /* - */:
    case 0x3a /* : */:
    case 0x3c /* < */:
    case 0x3d /* = */:
    case 0x3e /* > */:
    case 0x40 /* @ */:
    case 0x5b /* [ */:
    case 0x5c /* \\ */:
    case 0x5d /* ] */:
    case 0x5e /* ^ */:
    case 0x5f /* _ */:
    case 0x60 /* ` */:
    case 0x7b /* { */:
    case 0x7d /* } */:
    case 0x7e /* ~ */:
      return true
    default:
      return false
  }
}

const patchTextRule = (md) => {
  if (md.__spoilerTextRulePatched) {
    return
  }

  md.__spoilerTextRulePatched = true

  md.inline.ruler.at('text', (state, silent) => {
    let pos = state.pos

    while (pos < state.posMax) {
      const code = state.src.charCodeAt(pos)
      if (code === pipeMark || isDefaultTerminator(code)) {
        break
      }

      pos++
    }

    if (pos === state.pos) {
      return false
    }

    if (!silent) {
      state.pending += state.src.slice(state.pos, pos)
    }

    state.pos = pos
    return true
  })
}

const tokenize = frontPriorMode => (state, silent) => {
  if (silent) return false

  const start = state.pos
  const marker = state.src.charCodeAt(start)

  if (marker !== pipeMark) return false

  const scanned = state.scanDelims(state.pos, true)
  let len = scanned.length
  const ch = String.fromCharCode(marker)

  if (len < 2) return false

  let isOdd = false
  if (len % 2) {
    isOdd = true
    if (!frontPriorMode) {
      const token = state.push("text", "", 0)
      token.content = ch
    }
    len--
  }

  for (let i = 0; i < len; i += 2) {
    const token = state.push("text", "", 0)
    token.content = ch + ch

    state.delimiters.push({
      marker,
      length: 0, // disable "rule of 3" length checks meant for emphasis
      jump: i / 2, // 1 delimiter = 2 characters
      token: state.tokens.length - 1,
      end: -1,
      open: scanned.can_open,
      close: scanned.can_close
    })
  }

  state.pos += scanned.length
  if (isOdd && frontPriorMode) {
    state.pos--
  }

  return true
}

const postProcess = (state, delimiters) => {
  const loneMarkers = []

  for (const startDelim of delimiters) {
    if (startDelim.marker !== pipeMark) continue
    if (startDelim.end === -1) continue

    const endDelim = delimiters[startDelim.end]

    const tokenO = state.tokens[startDelim.token]
    tokenO.type = "spoiler_open"
    tokenO.tag = "span"
    tokenO.attrs = [["class", "spoiler"]]
    tokenO.nesting = 1
    tokenO.markup = "||"
    tokenO.content = ""

    const tokenC = state.tokens[endDelim.token]
    tokenC.type = "spoiler_close"
    tokenC.tag = "span"
    tokenC.nesting = -1
    tokenC.markup = "||"
    tokenC.content = ""

    if (
      state.tokens[endDelim.token - 1].type === "text" &&
      state.tokens[endDelim.token - 1].content === "|"
    ) {
      loneMarkers.push(endDelim.token - 1)
    }
  }

  // If a marker sequence has an odd number of characters, it's splitted
  // like this: `||||!` -> `!` + `||` + `||`, leaving one marker at the
  // start of the sequence.
  //
  // So, we have to move all those markers after subsequent spoiler_close tags.
  //
  while (loneMarkers.length) {
    const i = loneMarkers.pop()
    let j = i + 1

    while (
      j < state.tokens.length &&
      state.tokens[j].type === "spoiler_close"
    ) {
      j++
    }

    j--

    if (i !== j) {
      const token = state.tokens[j]
      state.tokens[j] = state.tokens[i]
      state.tokens[i] = token
    }
  }
}

export const spoiler = function(md, frontPriorMode = false) {
  patchTextRule(md)
  md.inline.ruler.before("emphasis", "spoiler", tokenize(frontPriorMode))
  md.inline.ruler2.before("emphasis", "spoiler", state => {
    postProcess(state, state.delimiters)

    
    for (const meta of state.tokens_meta) {
      if (meta && meta.delimiters) {
        postProcess(state, meta.delimiters)
      }
    }

    return true;
  })
}