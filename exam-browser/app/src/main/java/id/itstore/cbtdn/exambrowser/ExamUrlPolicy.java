package id.itstore.cbtdn.exambrowser;

import java.net.URI;
import java.net.URISyntaxException;

/** Keeps top-level browser navigation inside the CBT host. */
public final class ExamUrlPolicy {
    public static final String ALLOWED_HOST = "cbtdn.itstore.my.id";

    private ExamUrlPolicy() {
    }

    public static boolean isAllowed(String rawUrl) {
        if (rawUrl == null || rawUrl.isBlank()) {
            return false;
        }

        try {
            URI uri = new URI(rawUrl.trim());
            String scheme = uri.getScheme();
            String host = uri.getHost();
            int port = uri.getPort();

            return "https".equalsIgnoreCase(scheme)
                    && host != null
                    && ALLOWED_HOST.equalsIgnoreCase(host)
                    && uri.getUserInfo() == null
                    && (port == -1 || port == 443);
        } catch (URISyntaxException exception) {
            return false;
        }
    }
}
