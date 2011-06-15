import weechat

blacklisted = []

wl_last = 1

if weechat.register("chanpriority", "Flavius", "0.1", "GPL3", "Allows you to set chans with low priority; see / /* TODO */", "", ""):
    if not weechat.config_is_set_plugin("blacklisted"):
        weechat.config_set_plugin("blacklisted", "")
    else:
        t = weechat.config_get_plugin("blacklisted")
        blacklisted = t.split(",")

def on_join(data, signal, signal_data):
    global wl_last
    (chan, network, buffer) = joinpart_meta(data, signal, signal_data)

    buffers = weechat.infolist_get("buffer", "", "")
    buffer_cnt = infolist_number_max(buffers)

    if chan not in blacklisted:
        wl_last += 1
        weechat.buffer_set(buffer, "number", str(wl_last))
    weechat.infolist_free(buffers)
    return weechat.WEECHAT_RC_OK

def on_part(data, signal, signal_data):
    global wl_last
    (chan, network, buffer) = joinpart_meta(data, signal, signal_data)
    if chan not in blacklisted:
        wl_last -= 1

    return weechat.WEECHAT_RC_OK

def cmd_blacklist(data, buffer, args):
    weechat.prnt("", "-------------------")
    weechat.prnt("", "data: " + str(data))
    weechat.prnt("", "buffer: " + str(buffer))
    weechat.prnt("", "args: " + str(args))
    weechat.prnt("", "-------------------")
    return weechat.WEECHAT_RC_OK

def infolist_number_max(infolist):
    while weechat.infolist_next(infolist):
        pass
    weechat.infolist_prev(infolist)
    return weechat.infolist_integer(infolist, "number")

def joinpart_meta(data, signal, signal_data):
    chan = signal_data.rpartition(":")[-1]
    network = signal.partition(",")[0]
    buffer = weechat.buffer_search("irc", network + "." + chan)
    return (chan, network, buffer)

weechat.hook_signal("*,irc_in2_join", "on_join", "data")
weechat.hook_signal("*,irc_in2_part", "on_part", "data")
