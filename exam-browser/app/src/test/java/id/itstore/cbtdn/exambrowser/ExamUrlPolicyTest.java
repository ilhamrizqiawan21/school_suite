package id.itstore.cbtdn.exambrowser;

import static org.junit.Assert.assertFalse;
import static org.junit.Assert.assertTrue;

import org.junit.Test;

public final class ExamUrlPolicyTest {
    @Test
    public void allowsTargetHostOverHttps() {
        assertTrue(ExamUrlPolicy.isAllowed("https://cbtdn.itstore.my.id/"));
        assertTrue(ExamUrlPolicy.isAllowed("https://CBTDN.ITSTORE.MY.ID/?mapel=matematika"));
    }

    @Test
    public void rejectsOtherHostsAndSchemes() {
        assertFalse(ExamUrlPolicy.isAllowed("http://cbtdn.itstore.my.id/"));
        assertFalse(ExamUrlPolicy.isAllowed("https://evil.example/"));
        assertFalse(ExamUrlPolicy.isAllowed("https://cbtdn.itstore.my.id.evil.example/"));
        assertFalse(ExamUrlPolicy.isAllowed("intent://cbtdn.itstore.my.id/"));
    }

    @Test
    public void rejectsCredentialBearingAndUnexpectedPorts() {
        assertFalse(ExamUrlPolicy.isAllowed("https://student:secret@cbtdn.itstore.my.id/"));
        assertFalse(ExamUrlPolicy.isAllowed("https://cbtdn.itstore.my.id:8443/"));
        assertFalse(ExamUrlPolicy.isAllowed(""));
        assertFalse(ExamUrlPolicy.isAllowed(null));
    }
}
