<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$pageTitle = 'HAVOC AGENCY | IMPRESSUM';
$layout = 'editorial';
$styles = ['impressum.css'];
$backLink = ['', '← ZURÜCK ZUR STARTSEITE'];
require BASE_PATH . '/templates/header.php';
?>

    <div class="content-container">
        <main>
            <h1 class="page-title">IMPRESSUM //</h1>

            <!-- Anbieterkennzeichnung -->
            <div class="legal-section">
                <h2>Angaben gemäß § 5 TMG</h2>
                <p><strong>Havoc Agency</strong><br>Annenstraße 107<br>58453 Witten</p>

                <div class="meta-grid">
                    <div class="meta-label">Inhaber //</div>
                    <div class="meta-value">Rene Brauer</div>

                    <div class="meta-label">Telefon //</div>
                    <div class="meta-value">01771695943</div>

                    <div class="meta-label">E-Mail //</div>
                    <div class="meta-value"><a href="mailto:info@havoc-agency.com">info@havoc-agency.com</a></div>

                    <div class="meta-label">Register //</div>
                    <div class="meta-value">Amtsgericht Dortmund</div>
                </div>
            </div>

            <!-- Steuer-Daten -->
            <div class="legal-section">
                <h2>Steuer-Identifikation</h2>
                <div class="meta-grid">
                    <div class="meta-label">USt.-IdNr. //</div>
                    <div class="meta-value">DE269995336</div>

                    <div class="meta-label">Steuer-Nr. //</div>
                    <div class="meta-value">348/5020/3512</div>
                </div>
            </div>

            <!-- Bankverbindung -->
            <div class="legal-section">
                <h2>Bankverbindung</h2>
                <div class="meta-grid">
                    <div class="meta-label">Institut //</div>
                    <div class="meta-value">Qonto</div>

                    <div class="meta-label">IBAN //</div>
                    <div class="meta-value mono">DE31 1001 0123 4636 3187 83</div>

                    <div class="meta-label">BIC //</div>
                    <div class="meta-value mono">QNTODEB2XXX</div>
                </div>
            </div>

            <!-- Gesetzlicher Disclaimer -->
            <div class="legal-section disclaimer-text">
                <h2>Haftungsausschluss (Disclaimer)</h2>

                <p><strong>Haftung für Inhalte</strong><br>Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen. Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich.</p>

                <p><strong>Haftung für Links</strong><br>Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich. Die verlinkten Seiten wurden zum Zeitpunkt der Verlinkung auf mögliche Rechtsverstöße überprüft. Rechtswidrige Inhalte waren zum Zeitpunkt der Verlinkung nicht erkennbar. Eine permanente inhaltliche Kontrolle der verlinkten Seiten ist jedoch ohne konkrete Anhaltspunkte einer Rechtsverletzung nicht zumutbar. Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Links umgehend entfernen.</p>

                <p><strong>Urheberrecht</strong><br>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers. Downloads und Kopien dieser Seite sind nur für den privaten, nicht kommerziellen Gebrauch gestattet. Soweit die Inhalte auf dieser Seite nicht vom Betreiber erstellt wurden, werden die Urheberrechte Dritter beachtet. Insbesondere werden Inhalte Dritter als solche gekennzeichnet. Sollten Sie trotzdem auf eine Urheberrechtsverletzung aufmerksam werden, bitten wir um einen entsprechenden Hinweis. Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Inhalte umgehend entfernen.</p>
            </div>

            <!-- Verbraucherstreitbeilegung -->
            <div class="legal-section">
                <h2>EU-Streitschlichtung</h2>
                <p>Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: <a href="https://europa.eu" target="_blank" rel="noopener">https://europa.eu</a>.<br>Unsere E-Mail-Adresse finden Sie oben im Impressum. Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>
            </div>
        </main>
    </div>

<?php require BASE_PATH . '/templates/footer.php'; ?>
